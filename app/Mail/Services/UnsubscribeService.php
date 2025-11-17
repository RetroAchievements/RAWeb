<?php

declare(strict_types=1);

namespace App\Mail\Services;

use App\Community\Enums\SubscriptionSubjectType;
use App\Enums\UserPreference;
use App\Mail\Data\CategoryUnsubscribeData;
use App\Mail\Data\GranularUnsubscribeData;
use App\Mail\Data\UnsubscribeData;
use App\Models\Achievement;
use App\Models\Game;
use App\Models\Subscription;
use App\Models\User;
use App\Support\Cache\CacheKey;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\URL;

/**
 * Handles email unsubscribe functionality with a basic undo capability.
 *
 * This service generates secure, time-limited unsubscribe links using Laravel's
 * temporarySignedRoute() which creates URLs that expire after 90 days.
 *
 * The temporary URLs aren't stored anywhere. They're generated on-demand and
 * validated cryptographically.
 * @see https://laravel.com/docs/11.x/urls
 *
 * Two unsubscribe strategies are supported:
 *
 * 1. Granular: Unsubscribe from specific items (forum thread, game wall, etc.)
 *    Creates explicit unsubscribe records to override implicit subscriptions.
 *
 * 2. Category: Unsubscribe from entire notification categories (all forum replies, etc.)
 *    Updates user preference bitflags to disable categories globally.
 *
 * The undo mechanism uses cache-based tokens (24hr expiry) allowing users to
 * quickly revert accidental unsubscribes without permanent data loss.
 */
class UnsubscribeService
{
    public function generateGranularUrl(User $user, SubscriptionSubjectType $subjectType, int $subjectId): string
    {
        $data = new GranularUnsubscribeData(
            userId: $user->id,
            subjectType: $subjectType,
            subjectId: $subjectId,
        );

        return URL::temporarySignedRoute(
            'unsubscribe.show',
            now()->addDays(90),
            ['token' => base64_encode($data->toJson())]
        );
    }

    public function generateCategoryUrl(User $user, int $preferenceKey): string
    {
        $data = new CategoryUnsubscribeData(
            userId: $user->id,
            preference: $preferenceKey,
        );

        return URL::temporarySignedRoute(
            'unsubscribe.show',
            now()->addDays(90),
            ['token' => base64_encode($data->toJson())]
        );
    }

    /**
     * Process an unsubscribe request.
     *
     * $shouldGenerateUndoToken should be set to false for RFC 8058 one-click
     * POST requests where users will never see a confirmation page. It defaults
     * to true for GET requests to an Inertia UI.
     */
    public function processUnsubscribe(string $token, bool $shouldGenerateUndoToken = true): array
    {
        try {
            $json = base64_decode($token);
            $payload = json_decode($json, true);

            if (!$payload || !isset($payload['type'])) {
                return ['success' => false, 'errorCode' => 'invalid_token'];
            }

            $data = match ($payload['type']) {
                'granular' => GranularUnsubscribeData::from($payload),
                'category' => CategoryUnsubscribeData::from($payload),
                default => null,
            };

            if (!$data) {
                return ['success' => false, 'errorCode' => 'invalid_type'];
            }
        } catch (Exception $e) {
            return ['success' => false, 'errorCode' => 'invalid_format'];
        }

        $user = User::find($data->userId);
        if (!$user) {
            return ['success' => false, 'errorCode' => 'user_not_found'];
        }

        // Only capture the previous subscription state if we're generating an undo token.
        if ($shouldGenerateUndoToken && $data instanceof GranularUnsubscribeData) {
            // Check if there's an existing subscription record to capture its state.
            $existingSubscription = Subscription::where('user_id', $user->id)
                ->where('subject_type', $data->subjectType)
                ->where('subject_id', $data->subjectId)
                ->first();

            // Store the previous state in the undo data.
            // null means no explicit subscription existed.
            // true means they were explicitly subscribed.
            // false means they were explicitly unsubscribed.
            $data->previousState = $existingSubscription?->state;
        }

        // Generate an undo token only if requested.
        $undoToken = $shouldGenerateUndoToken ? $this->generateUndoToken($data) : null;

        if ($data instanceof GranularUnsubscribeData) {
            // Handle granular unsubscribe from a specific subscription.
            // Create or update a subscription record with state=0 to explicitly unsubscribe.
            // This handles both explicit subscriptions (updates them) and implicit
            // subscriptions (creates an explicit unsubscribe record).
            Subscription::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'subject_type' => $data->subjectType,
                    'subject_id' => $data->subjectId,
                ],
                [
                    'state' => false, // set to false rather than deleting to prevent implicit re-subscription
                ]
            );

            $descriptionData = $this->getGranularDescription($data->subjectType, $data->subjectId);
        } elseif ($data instanceof CategoryUnsubscribeData) {
            $currentPrefs = $user->websitePrefs;
            $newPrefs = $currentPrefs & ~(1 << $data->preference);
            $user->websitePrefs = $newPrefs;
            $user->save();

            $descriptionData = $this->getCategoryDescription($data->preference);
        }

        return [
            'success' => true,
            'descriptionKey' => $descriptionData['key'],
            'descriptionParams' => $descriptionData['params'] ?? null,
            'undoToken' => $undoToken,
            'user' => $user,
        ];
    }

    /**
     * Generate an undo token that expires in 24 hours.
     */
    public function generateUndoToken(UnsubscribeData $data): string
    {
        $token = bin2hex(random_bytes(32));

        Cache::put(
            CacheKey::buildUnsubscribeUndoTokenCacheKey($token),
            $data->toJson(),
            now()->addHours(24)
        );

        return $token;
    }

    public function processUndo(string $token): array
    {
        $json = Cache::get(CacheKey::buildUnsubscribeUndoTokenCacheKey($token));

        if (!$json) {
            return ['success' => false, 'errorCode' => 'undo_expired'];
        }

        try {
            $payload = json_decode($json, true);

            $data = match ($payload['type']) {
                'granular' => GranularUnsubscribeData::from($payload),
                'category' => CategoryUnsubscribeData::from($payload),
                default => null,
            };

            if (!$data) {
                return ['success' => false, 'errorCode' => 'invalid_undo'];
            }
        } catch (Exception $e) {
            return ['success' => false, 'errorCode' => 'invalid_undo_format'];
        }

        $user = User::find($data->userId);
        if (!$user) {
            return ['success' => false, 'errorCode' => 'user_not_found'];
        }

        if ($data instanceof GranularUnsubscribeData) {
            // Restore the subscription to its previous state.
            if ($data->previousState === null) {
                // No explicit subscription existed before, so remove the record entirely.
                // This will revert to implicit subscription if the user has commented,
                // or no subscription if they haven't.
                Subscription::where('user_id', $user->id)
                    ->where('subject_type', $data->subjectType)
                    ->where('subject_id', $data->subjectId)
                    ->delete();
            } else {
                // An explicit subscription existed before, so restore its state.
                Subscription::where('user_id', $user->id)
                    ->where('subject_type', $data->subjectType)
                    ->where('subject_id', $data->subjectId)
                    ->update(['state' => $data->previousState]);
            }
        } elseif ($data instanceof CategoryUnsubscribeData) {
            $currentPrefs = $user->websitePrefs;
            $newPrefs = $currentPrefs | (1 << $data->preference);
            $user->websitePrefs = $newPrefs;
            $user->save();
        }

        // Remove the undo token after use.
        Cache::forget(CacheKey::buildUnsubscribeUndoTokenCacheKey($token));

        return ['success' => true];
    }

    private function getGranularDescription(SubscriptionSubjectType $subjectType, int $subjectId): array
    {
        switch ($subjectType) {
            case SubscriptionSubjectType::ForumTopic:
                return ['key' => 'unsubscribeSuccess-forumThread'];

            case SubscriptionSubjectType::GameWall:
                $game = Game::find($subjectId);

                return [
                    'key' => 'unsubscribeSuccess-gameWall',
                    'params' => ['gameTitle' => $game->title ?? 'Unknown Game'],
                ];

            case SubscriptionSubjectType::Achievement:
                $achievement = Achievement::find($subjectId);

                return [
                    'key' => 'unsubscribeSuccess-achievement',
                    'params' => ['achievementTitle' => $achievement->title ?? 'Unknown Achievement'],
                ];

            case SubscriptionSubjectType::UserWall:
                $user = User::find($subjectId);

                return [
                    'key' => 'unsubscribeSuccess-userWall',
                    'params' => ['userName' => $user->display_name ?? 'Unknown User'],
                ];

            case SubscriptionSubjectType::GameTickets:
                $game = Game::find($subjectId);

                return [
                    'key' => 'unsubscribeSuccess-gameTickets',
                    'params' => ['gameTitle' => $game->title ?? 'Unknown Game'],
                ];

            case SubscriptionSubjectType::GameAchievements:
                $game = Game::find($subjectId);

                return [
                    'key' => 'unsubscribeSuccess-gameAchievements',
                    'params' => ['gameTitle' => $game->title ?? 'Unknown Game'],
                ];

            default:
                return ['key' => 'unsubscribeSuccess-unknown'];
        }
    }

    private function getCategoryDescription(int $preference): array
    {
        switch ($preference) {
            case UserPreference::EmailOn_ActivityComment:
                return ['key' => 'unsubscribeSuccess-allActivityComments'];

            case UserPreference::EmailOn_AchievementComment:
                return ['key' => 'unsubscribeSuccess-allAchievementComments'];

            case UserPreference::EmailOn_UserWallComment:
                return ['key' => 'unsubscribeSuccess-allUserWallComments'];

            case UserPreference::EmailOn_ForumReply:
                return ['key' => 'unsubscribeSuccess-allForumReplies'];

            case UserPreference::EmailOn_Followed:
                return ['key' => 'unsubscribeSuccess-allFollowerNotifications'];

            case UserPreference::EmailOn_PrivateMessage:
                return ['key' => 'unsubscribeSuccess-allPrivateMessages'];

            case UserPreference::EmailOn_TicketActivity:
                return ['key' => 'unsubscribeSuccess-allTicketActivity'];

            default:
                return ['key' => 'unsubscribeSuccess-unknown'];
        }
    }
}

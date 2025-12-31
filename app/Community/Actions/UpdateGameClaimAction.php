<?php

declare(strict_types=1);

namespace App\Community\Actions;

use App\Community\Enums\ArticleType;
use App\Community\Enums\AwardType;
use App\Community\Enums\ClaimSetType;
use App\Community\Enums\ClaimSpecial;
use App\Community\Enums\ClaimStatus;
use App\Community\Enums\ClaimType;
use App\Community\Enums\UserGameListType;
use App\Mail\SetAchievementsPublishedNotificationMail;
use App\Mail\SetRevisionNotificationMail;
use App\Models\AchievementSetClaim;
use App\Models\Game;
use App\Models\PlayerBadge;
use App\Models\User;
use App\Models\UserGameListEntry;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

class UpdateGameClaimAction
{
    public function execute(AchievementSetClaim $claim, array $newValues): void
    {
        /** @var User $currentUser */
        $currentUser = Auth::user();

        $auditMessage = "{$currentUser->display_name} updated {$claim->user->display_name}'s claim.";

        if (array_key_exists('type', $newValues)) {
            $newType = ClaimType::from($newValues['type']);
            if ($claim->claim_type !== $newType) {
                $claim->claim_type = $newType;

                $auditMessage .= " Claim Type: " . $newType->label() . '.';
            }
        }

        if (array_key_exists('set_type', $newValues)) {
            $newSetType = ClaimSetType::from($newValues['set_type']);
            if ($claim->set_type !== $newSetType) {
                $claim->set_type = $newSetType;

                $auditMessage .= " Set Type: " . $newSetType->label() . '.';
            }
        }

        if (array_key_exists('status', $newValues)) {
            $newStatus = ClaimStatus::from($newValues['status']);
            if ($claim->status !== $newStatus) {
                $claim->status = $newStatus;

                $auditMessage .= " Claim Status: " . $newStatus->label() . '.';

                if (!$newStatus->isActive()) {
                    $claim->finished_at = Carbon::now();

                    if ($newStatus === ClaimStatus::Complete) {
                        $this->processCompletedClaim($claim, $currentUser);
                    }
                }
            }
        }

        if (array_key_exists('special', $newValues)) {
            $newSpecial = ClaimSpecial::from($newValues['special']);
            if ($claim->special_type !== $newSpecial) {
                $claim->special_type = $newSpecial;

                $auditMessage .= " Special: " . $newSpecial->label() . '.';
            }
        }

        if (array_key_exists('claimed', $newValues)) {
            $newDate = $newValues['claimed'];
            if ($claim->created_at->notEqualTo($newDate)) {
                $claim->created_at = $newDate;

                $auditMessage .= " Claim Date: $newDate.";
            }
        }

        if (array_key_exists('finished', $newValues)) {
            $newDate = $newValues['finished'];
            if ($claim->finished_at->notEqualTo($newDate)) {
                $claim->finished_at = $newDate;

                $auditMessage .= " End Date: $newDate.";
            }
        }

        if ($claim->isDirty()) {
            $claim->save();

            addArticleComment("Server", ArticleType::SetClaim, $claim->game_id, $auditMessage);
        }
    }

    private function processCompletedClaim(AchievementSetClaim $claim, User $currentUser): void
    {
        addArticleComment("Server", ArticleType::SetClaim, $claim->game_id, "Claim completed by {$currentUser->display_name}");

        // Also complete any collaboration claims.
        $game = Game::find($claim->game_id);
        $game->achievementSetClaims()->active()->update(['status' => $claim->status, 'finished_at' => $claim->finished_at]);

        if ($claim->set_type === ClaimSetType::Revision) {
            // Send email to users who had previously mastered the set.
            $userAwards = PlayerBadge::with('user')
                ->where('AwardData', $game->id)
                ->where('AwardType', AwardType::Mastery)
                ->get();

            foreach ($userAwards as $userAward) {
                if ($userAward->user && !$userAward->user->banned_at) {
                    Mail::to($userAward->user)->queue(new SetRevisionNotificationMail(
                        $userAward->user,
                        $game,
                        $userAward->AwardDataExtra === 1
                    ));
                }
            }
        } else {
            $setRequests = UserGameListEntry::where('game_id', $game->id)
                ->where('type', UserGameListType::AchievementSetRequest)
                ->with('user')
                ->get();

            foreach ($setRequests as $setRequest) {
                if ($setRequest->user && !$setRequest->user->banned_at) {
                    Mail::to($setRequest->user)->queue(new SetAchievementsPublishedNotificationMail(
                        $setRequest->user,
                        $game
                    ));
                }
            }
        }

        $webhookUrl = config('services.discord.webhook.claims');
        if (!empty($webhookUrl)) {
            $payload = [
                'username' => 'Claim Bot',
                'avatar_url' => media_asset('UserPic/QATeam.png'),
                'content' => route('game.show', $game) . "\n:white_check_mark: " .
                             "Claim completed by " . $currentUser->display_name,
            ];
            (new Client())->post($webhookUrl, ['json' => $payload]);
        }
    }
}

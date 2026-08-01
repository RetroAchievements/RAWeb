<?php

declare(strict_types=1);

namespace App\Community\Actions;

use App\Community\Enums\SubscriptionSubjectType;
use Illuminate\Support\Str;

/**
 * Builds the daily digest subject line.
 *
 * The subject names the single most important item the email carries, then tallies
 * everything else.
 *
 * Examples:
 *   "Achievements released for Sonic the Hedgehog"
 *   "Achievements released for Sonic the Hedgehog, and 4 more updates"
 *   "Your screenshot was reviewed"
 *   "Your screenshots were reviewed, and 2 more updates"
 *   "New replies to your posts, and 1 more update"
 *   "New comments on things you follow"
 */
class BuildDailyDigestSubjectAction
{
    /**
     * Our precedence order for choosing a winner. Anything missing from this list
     * will fall through to the generic conversation headline.
     *
     * @var string[]
     */
    private const HEADLINE_PRIORITY = [
        SubscriptionSubjectType::AchievementSetRelease->value,
        SubscriptionSubjectType::GameScreenshotDecision->value,
        SubscriptionSubjectType::ForumTopic->value,
        SubscriptionSubjectType::AchievementTicket->value,
    ];

    /**
     * @param array<int, array<string, mixed>> $notificationItems
     */
    public function execute(array $notificationItems): string
    {
        if (empty($notificationItems)) {
            return 'Your RetroAchievements updates';
        }

        $headline = $this->buildHeadline($notificationItems);

        $remainingCount = count($notificationItems) - 1;
        if ($remainingCount < 1) {
            return $headline;
        }

        return "{$headline}, and {$remainingCount} more " . Str::plural('update', $remainingCount);
    }

    /**
     * @param array<int, array<string, mixed>> $notificationItems
     */
    private function buildHeadline(array $notificationItems): string
    {
        foreach (self::HEADLINE_PRIORITY as $type) {
            $matches = array_values(array_filter(
                $notificationItems,
                fn (array $item) => ($item['type'] ?? null) === $type
            ));

            if (!empty($matches)) {
                return $this->headlineForType($type, $matches);
            }
        }

        return 'New comments on things you follow';
    }

    /**
     * @param array<int, array<string, mixed>> $matches
     */
    private function headlineForType(string $type, array $matches): string
    {
        return match ($type) {
            SubscriptionSubjectType::AchievementSetRelease->value => "Achievements released for {$matches[0]['gameTitle']}",

            SubscriptionSubjectType::GameScreenshotDecision->value => $this->screenshotCount($matches) === 1
                    ? 'Your screenshot was reviewed'
                    : 'Your screenshots were reviewed',

            SubscriptionSubjectType::ForumTopic->value => 'New replies to your posts',

            SubscriptionSubjectType::AchievementTicket->value => 'New activity on your tickets',

            default => 'New comments on things you follow',
        };
    }

    /**
     * A screenshot item can represent several decisions for one game's stuff, so the
     * plural depends on the decisions behind the items rather than the actual item count.
     *
     * @param array<int, array<string, mixed>> $matches
     */
    private function screenshotCount(array $matches): int
    {
        $total = 0;
        foreach ($matches as $match) {
            $total += (int) ($match['count'] ?? 1);
        }

        return $total;
    }
}

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
     * @param array<int, array<string, mixed>> $notificationItems
     */
    public function execute(array $notificationItems): string
    {
        if (empty($notificationItems)) {
            return 'Your RetroAchievements updates';
        }

        [$headline, $coveredCount] = $this->buildHeadline($notificationItems);

        $remainingCount = count($notificationItems) - $coveredCount;
        if ($remainingCount < 1) {
            return $headline;
        }

        return "{$headline}, and {$remainingCount} more " . Str::plural('update', $remainingCount);
    }

    /**
     * Returns the headline and the number of items it already speaks for. A headline
     * that names one thing covers a single item. A headline that describes a whole
     * group covers every item in that group, so the tally does not repeat them.
     *
     * @param array<int, array<string, mixed>> $notificationItems
     * @return array{0: string, 1: int}
     */
    private function buildHeadline(array $notificationItems): array
    {
        foreach (SubscriptionSubjectType::digestHeadlinePriority() as $type) {
            $matches = array_values(array_filter(
                $notificationItems,
                fn (array $item) => ($item['type'] ?? null) === $type->value
            ));

            if (!empty($matches)) {
                // the release headline intentionally names only one game, so any other release still needs a tally
                $coveredCount = $type === SubscriptionSubjectType::AchievementSetRelease ? 1 : count($matches);

                return [$this->headlineForType($type, $matches), $coveredCount];
            }
        }

        return ['New comments on things you follow', count($notificationItems)];
    }

    /**
     * @param array<int, array<string, mixed>> $matches
     */
    private function headlineForType(SubscriptionSubjectType $type, array $matches): string
    {
        return match ($type) {
            SubscriptionSubjectType::AchievementSetRelease => "Achievements released for {$matches[0]['gameTitle']}",

            SubscriptionSubjectType::GameScreenshotDecision => $this->screenshotCount($matches) === 1
                    ? 'Your screenshot was reviewed'
                    : 'Your screenshots were reviewed',

            SubscriptionSubjectType::ForumTopic => 'New replies to your posts',

            SubscriptionSubjectType::AchievementTicket => 'New activity on your tickets',

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
        return array_sum(array_map(fn (array $match) => (int) ($match['count'] ?? 1), $matches));
    }
}

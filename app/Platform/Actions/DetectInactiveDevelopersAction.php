<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Community\Enums\CommentableType;
use App\Models\Achievement;
use App\Models\AchievementSetClaim;
use App\Models\Comment;
use App\Models\Leaderboard;
use App\Models\MemoryNote;
use App\Models\Role;
use App\Models\Ticket;
use App\Models\Trigger;
use App\Models\User;
use App\Support\Alerts\DeveloperInactivityAlert;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Spatie\Activitylog\Models\Activity;

class DetectInactiveDevelopersAction
{
    private const DEDUPE_TTL_DAYS = 90;
    private const MAX_ENTRIES_PER_ALERT = 8;

    public function execute(): int
    {
        if (!DeveloperInactivityAlert::webhookUrl()) {
            return 0;
        }

        $overallInactivityThreshold = Carbon::now()->subMonths(3);
        $developerInactivityThreshold = Carbon::now()->subMonths(6);

        $alertEntries = [];

        $developers = User::query()
            ->whereNull('banned_at')
            ->whereHas('roles', fn (Builder $query) => $query->where('name', Role::DEVELOPER))
            ->whereDoesntHave('roles', fn (Builder $query) => $query->where('name', Role::ROOT))
            ->orderBy('display_name')
            ->get();

        foreach ($developers as $developer) {
            $finding = $this->firstInactivityFinding(
                developer: $developer,
                overallInactivityThreshold: $overallInactivityThreshold,
                developerInactivityThreshold: $developerInactivityThreshold,
            );

            if ($finding === null || !$this->markFindingAsNew($developer, $finding)) {
                continue;
            }

            $alertEntries[] = [
                'displayName' => $developer->display_name,
                'finding' => $finding,
            ];
        }

        foreach (array_chunk($alertEntries, self::MAX_ENTRIES_PER_ALERT) as $entries) {
            (new DeveloperInactivityAlert($entries))->send();
        }

        return count($alertEntries);
    }

    /**
     * @return array{reason: string, threshold: string, lastActivityAt: string|null}|null
     */
    private function firstInactivityFinding(
        User $developer,
        Carbon $overallInactivityThreshold,
        Carbon $developerInactivityThreshold,
    ): ?array {
        $lastOverallActivityAt = $developer->last_activity_at;

        if ($lastOverallActivityAt === null || $lastOverallActivityAt->lt($overallInactivityThreshold)) {
            return [
                'reason' => DeveloperInactivityAlert::REASON_OVERALL_INACTIVITY,
                'threshold' => '3-month',
                'lastActivityAt' => $lastOverallActivityAt?->toDateTimeString(),
            ];
        }

        if ($this->hasDeveloperActivitySince($developer, $developerInactivityThreshold)) {
            return null;
        }

        return [
            'reason' => DeveloperInactivityAlert::REASON_DEVELOPER_INACTIVITY,
            'threshold' => '6-month',
            'lastActivityAt' => $this->lastDeveloperActivityAt($developer)?->toDateTimeString(),
        ];
    }

    /**
     * @param array{reason: string, threshold: string, lastActivityAt: string|null} $finding
     */
    private function markFindingAsNew(User $developer, array $finding): bool
    {
        $cacheKey = sprintf(
            'alerts:developer-inactivity:%s',
            hash('sha256', implode('|', [
                $developer->id,
                $finding['reason'],
                $finding['lastActivityAt'] ?? 'never',
            ]))
        );

        return Cache::add($cacheKey, true, Carbon::now()->addDays(self::DEDUPE_TTL_DAYS));
    }

    private function hasDeveloperActivitySince(User $developer, Carbon $since): bool
    {
        // publishing memory notes, achievements, or leaderboards
        if (Activity::query()
            ->where('causer_type', $developer->getMorphClass())
            ->where('causer_id', $developer->id)
            ->whereIn('subject_type', $this->authoredSubjectTypes())
            ->where('created_at', '>=', $since)
            ->exists()
        ) {
            return true;
        }

        // updating trigger logic
        if (Trigger::query()
            ->where('user_id', $developer->id)
            ->where('updated_at', '>=', $since)
            ->exists()
        ) {
            return true;
        }

        // creating a claim
        if (AchievementSetClaim::query()
            ->where('user_id', $developer->id)
            ->where('created_at', '>=', $since)
            ->exists()
        ) {
            return true;
        }

        // commenting on a ticket
        if (Comment::query()
            ->where('user_id', $developer->id)
            ->where('commentable_type', CommentableType::AchievementTicket)
            ->where('created_at', '>=', $since)
            ->exists()
        ) {
            return true;
        }

        // resolving a ticket
        if (Ticket::query()
            ->where('resolver_id', $developer->id)
            ->where('resolved_at', '>=', $since)
            ->exists()
        ) {
            return true;
        }

        return false;
    }

    private function lastDeveloperActivityAt(User $developer): ?Carbon
    {
        return $this->latestTimestamp([
            // publishing memory notes, achievements, or leaderboards
            $this->lastAuthoredAuditLogAt($developer),

            // updating trigger logic
            $this->maxTimestamp(
                Trigger::query()->where('user_id', $developer->id),
                'updated_at',
            ),

            // creating a claim
            $this->maxTimestamp(
                AchievementSetClaim::query()->where('user_id', $developer->id),
                'created_at',
            ),

            // commenting on a ticket
            $this->maxTimestamp(
                Comment::query()
                    ->where('user_id', $developer->id)
                    ->where('commentable_type', CommentableType::AchievementTicket),
                'created_at',
            ),

            // resolving a ticket
            $this->maxTimestamp(
                Ticket::query()
                    ->where('resolver_id', $developer->id)
                    ->whereNotNull('resolved_at'),
                'resolved_at',
            ),
        ]);
    }

    /**
     * Audit log rows are the only timestamps that prove the developer themselves authored a change.
     *
     * Row-level columns like achievements.modified_at are bumped by other developers, admins, or
     * automated processes, so that isn't reliable for this heuristic.
     */
    private function lastAuthoredAuditLogAt(User $developer): ?Carbon
    {
        return $this->toCarbon(
            Activity::query()
                ->where('causer_type', $developer->getMorphClass())
                ->where('causer_id', $developer->id)
                ->whereIn('subject_type', $this->authoredSubjectTypes())
                ->max('created_at')
        );
    }

    /**
     * @return list<string>
     */
    private function authoredSubjectTypes(): array
    {
        return [
            (new Achievement())->getMorphClass(),
            (new Leaderboard())->getMorphClass(),
            (new MemoryNote())->getMorphClass(),
        ];
    }

    /**
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param Builder<TModel> $query
     */
    private function maxTimestamp(Builder $query, string $column): ?Carbon
    {
        return $this->toCarbon($query->max($column));
    }

    /**
     * @param array<int, Carbon|null> $timestamps
     */
    private function latestTimestamp(array $timestamps): ?Carbon
    {
        $timestamps = array_filter($timestamps);

        return $timestamps === [] ? null : max($timestamps);
    }

    private function toCarbon(?string $value): ?Carbon
    {
        return $value === null ? null : Carbon::parse($value);
    }
}

<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Community\Enums\CommentableType;
use App\Data\UserData;
use App\Models\Achievement;
use App\Models\Comment;
use App\Models\Game;
use App\Models\Trigger;
use App\Models\User;
use App\Platform\Data\AchievementChangelogEntryData;
use App\Platform\Data\ChangelogFieldChangeData;
use App\Platform\Enums\AchievementChangelogEntryType;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Spatie\Activitylog\Models\Activity;

class BuildAchievementChangelogAction
{
    /**
     * The cutoff date after which we only use activitylog entries.
     * Legacy comments overlap with activitylog starting Feb 2024,
     * so we use legacy comments only before this date to avoid duplicates.
     */
    private const ACTIVITYLOG_CUTOFF = '2024-02-01';

    /**
     * The triggers table became the source of truth for logic
     * changes starting Jan 2025. Before this, logic changes are
     * only recorded in legacy comments.
     */
    private const TRIGGERS_CUTOFF = '2025-01-01';

    private const MAX_ENTRIES = 100;

    /** @var array<string, User|null> Pre-resolved user lookup to avoid N+1 queries on legacy comments. */
    private array $userCache = [];

    /**
     * The activitylog uses both legacy PascalCase and modern snake_case
     * property names depending on when the entry was recorded.
     */
    private const FIELD_NORMALIZE_MAP = [
        'Description' => 'description',
        'Title' => 'title',
        'Points' => 'points',
        'Flags' => 'is_promoted',
        'BadgeName' => 'image_name',
        'description' => 'description',
        'title' => 'title',
        'points' => 'points',
        'is_promoted' => 'is_promoted',
        'image_name' => 'image_name',
        'embed_url' => 'embed_url',
        'trigger_definition' => 'trigger_definition',
        'game_id' => 'game_id',
        'type' => 'type',
    ];

    private const FIELD_TO_ENTRY_TYPE = [
        'description' => AchievementChangelogEntryType::DescriptionUpdated,
        'title' => AchievementChangelogEntryType::TitleUpdated,
        'points' => AchievementChangelogEntryType::PointsChanged,
        'image_name' => AchievementChangelogEntryType::BadgeUpdated,
        'embed_url' => AchievementChangelogEntryType::EmbedUrlUpdated,
        'trigger_definition' => AchievementChangelogEntryType::LogicUpdated,
        'game_id' => AchievementChangelogEntryType::MovedToDifferentGame,
    ];

    private const LEGACY_FIELD_TO_ENTRY_TYPE = [
        'logic' => AchievementChangelogEntryType::LogicUpdated,
        'description' => AchievementChangelogEntryType::DescriptionUpdated,
        'title' => AchievementChangelogEntryType::TitleUpdated,
        'points' => AchievementChangelogEntryType::PointsChanged,
        'badge' => AchievementChangelogEntryType::BadgeUpdated,
        'embed url' => AchievementChangelogEntryType::EmbedUrlUpdated,
        'wording' => AchievementChangelogEntryType::DescriptionUpdated,
        'type' => AchievementChangelogEntryType::TypeChanged,
    ];

    /**
     * Raw type values are snake_case enum strings.
     * We present them as human-readable labels for the changelog UI.
     */
    private const ACHIEVEMENT_TYPE_LABELS = [
        'progression' => 'Progression',
        'win_condition' => 'Win Condition',
        'missable' => 'Missable',
    ];

    /**
     * @return AchievementChangelogEntryData[]
     */
    public function execute(Achievement $achievement): array
    {
        // Fetch all system comments for this achievement once, then
        // partition by cutoff date so we don't hit the table twice.
        $allSystemComments = Comment::query()
            ->where('commentable_type', CommentableType::Achievement->value)
            ->where('commentable_id', $achievement->id)
            ->where('user_id', Comment::SYSTEM_USER_ID)
            ->orderByDesc('created_at')
            ->get();

        $cutoff = Carbon::parse(self::ACTIVITYLOG_CUTOFF);
        [$preCutoffComments, $postCutoffComments] = $allSystemComments->partition(
            fn (Comment $c) => $c->created_at->lt($cutoff)
        );

        $activitylogEntries = $this->getActivitylogEntries($achievement);
        $triggerEntries = $this->getTriggerEntries($achievement);
        $legacyEntries = $this->getLegacyCommentEntries($preCutoffComments);

        // Many activitylog entries unfortunately lack a causer (~91K across the DB).
        // Causer tracking was fixed in PR #2341 (May 2024). As a second
        // pass, try to recover users from post-cutoff legacy comments
        // that were written for the same action.
        $this->backfillUsersFromComments($postCutoffComments, $activitylogEntries);

        $merged = array_merge($activitylogEntries, $triggerEntries, $legacyEntries);
        usort(
            $merged,
            fn (AchievementChangelogEntryData $a, AchievementChangelogEntryData $b) => $b->createdAt->timestamp <=> $a->createdAt->timestamp,
        );
        $merged = $this->collapseConsecutiveEntries($merged);

        // Every achievement should have a "Created" anchor at the bottom of its changelog timeline.
        $hasCreated = collect($merged)->contains(
            fn (AchievementChangelogEntryData $entry) => $entry->type === AchievementChangelogEntryType::Created
        );

        if (!$hasCreated) {
            $developer = $achievement->developer;

            $merged[] = new AchievementChangelogEntryData(
                type: AchievementChangelogEntryType::Created,
                createdAt: Carbon::parse($achievement->created_at),
                user: $developer ? $this->buildUserData($developer) : null,
            );
        }

        return array_slice($merged, 0, self::MAX_ENTRIES);
    }

    /**
     * Collapse consecutive entries that have the same type and user
     * into a single entry with a count. This reduces visual noise
     * from repeated "Logic updated" or "Achievement edited" changes.
     *
     * @param AchievementChangelogEntryData[] $entries already sorted descending by date
     * @return AchievementChangelogEntryData[]
     */
    private function collapseConsecutiveEntries(array $entries): array
    {
        if (count($entries) <= 1) {
            return $entries;
        }

        $collapsed = [];
        $current = $entries[0];
        $REMOVAL_SENTINEL = -1;

        for ($i = 1, $len = count($entries); $i < $len; $i++) {
            $next = $entries[$i];

            $isSameType = $current->type === $next->type;
            $isSameUser = $current->user?->displayName === $next->user?->displayName;

            if ($isSameType && $isSameUser) {
                $current->count++;

                // For types with field changes, merge to show the overall change
                // (oldest oldValue -> newest newValue) instead of a meaningless repeat count.
                if (!empty($next->fieldChanges) && !empty($current->fieldChanges)) {
                    $current->fieldChanges[0]->oldValue = $next->fieldChanges[0]->oldValue;

                    // If the values ultimately ended up the same (eg: 4->5 then 5->4), mark for removal.
                    if ($current->fieldChanges[0]->oldValue === $current->fieldChanges[0]->newValue) {
                        $current->fieldChanges = [];
                        $current->count = $REMOVAL_SENTINEL; // net-zero change, remove this entry entirely
                    }
                }
            } else {
                $collapsed[] = $current;
                $current = $next;
            }
        }

        $collapsed[] = $current;

        // Remove entries where collapsing ultimately produced a net-zero change.
        return array_values(array_filter($collapsed, fn ($e) => $e->count !== $REMOVAL_SENTINEL));
    }

    /**
     * When activitylog entries are missing a causer, try to recover
     * the user from a nearby activitylog entry that does have one.
     * This handles cases where a single user action produces multiple
     * activity entries but only some have the causer recorded.
     *
     * This is O(n*m), but bounded by MAX_ENTRIES (100), so not really a concern.
     *
     * @param EloquentCollection<int, Activity> $activities
     */
    private function backfillMissingCausers(EloquentCollection $activities): void
    {
        $withCausers = $activities->filter(fn (Activity $a) => $a->causer_id !== null);
        if ($withCausers->isEmpty()) {
            return;
        }

        foreach ($activities as $activity) {
            if ($activity->causer_id !== null) {
                continue;
            }

            // Find a nearby activity (within 5 minutes) that has a causer.
            foreach ($withCausers as $other) {
                if (abs($activity->created_at->diffInSeconds($other->created_at)) <= 300) {
                    $activity->setRelation('causer', $other->causer);
                    break;
                }
            }
        }
    }

    /**
     * For activitylog entries still missing a user after the nearby-activity
     * backfill, try to recover attribution from post-cutoff legacy comments
     * that were written around the same time.
     *
     * @param Collection<int, Comment> $postCutoffComments
     * @param AchievementChangelogEntryData[] $entries
     */
    private function backfillUsersFromComments(Collection $postCutoffComments, array $entries): void
    {
        $hasMissing = collect($entries)->contains(
            fn (AchievementChangelogEntryData $entry) => $entry->user === null
        );

        if (!$hasMissing || $postCutoffComments->isEmpty()) {
            return;
        }

        // Batch-resolve display names before the loop to avoid N+1 queries.
        $this->preResolveUsersFromComments($postCutoffComments);

        foreach ($entries as $entry) {
            if ($entry->user !== null) {
                continue;
            }

            foreach ($postCutoffComments as $comment) {
                $commentTime = Carbon::parse($comment->created_at);
                if (abs($entry->createdAt->diffInSeconds($commentTime)) > 300) {
                    continue;
                }

                $displayName = $this->extractDisplayNameFromComment($comment->body);
                if ($displayName !== null) {
                    $resolved = $this->resolveUserByDisplayName($displayName);
                    if ($resolved !== null) {
                        $entry->user = $resolved;
                        break;
                    }
                }
            }
        }
    }

    /**
     * @return AchievementChangelogEntryData[]
     */
    private function getActivitylogEntries(Achievement $achievement): array
    {
        $activities = Activity::query()
            ->where('subject_type', 'achievement')
            ->where('subject_id', $achievement->id)
            ->where('created_at', '>=', self::ACTIVITYLOG_CUTOFF)
            ->whereIn('event', ['created', 'updated', 'deleted', 'restored'])
            ->orderByDesc('created_at')
            ->limit(self::MAX_ENTRIES)
            ->get();

        // Manually load causers including soft-deleted users, since
        // the default morphTo causer relationship excludes them.
        $causerIds = $activities->pluck('causer_id')->filter()->unique()->values();
        $causers = User::withTrashed()->whereIn('id', $causerIds)->get()->keyBy('id');
        foreach ($activities as $activity) {
            if ($activity->causer_id && $causers->has($activity->causer_id)) {
                $activity->setRelation('causer', $causers->get($activity->causer_id));
            }
        }

        // Some activities lack a causer. Recover it from a nearby
        // activity that was likely part of the same user action.
        $this->backfillMissingCausers($activities);

        $activities->loadMissing('causer');

        $entries = [];
        foreach ($activities as $activity) {
            array_push($entries, ...$this->transformActivityToEntries($activity));
        }

        return $entries;
    }

    /**
     * @return AchievementChangelogEntryData[]
     */
    private function getTriggerEntries(Achievement $achievement): array
    {
        $triggers = Trigger::query()
            ->where('triggerable_type', 'achievement')
            ->where('triggerable_id', $achievement->id)
            ->where('created_at', '>=', self::TRIGGERS_CUTOFF)
            ->whereNotNull('parent_id')
            ->with('user')
            ->orderByDesc('created_at')
            ->limit(self::MAX_ENTRIES)
            ->get();

        $entries = [];
        foreach ($triggers as $trigger) {
            $user = $trigger->user
                ? $this->buildUserData($trigger->user)
                : null;

            $entries[] = new AchievementChangelogEntryData(
                type: AchievementChangelogEntryType::LogicUpdated,
                createdAt: Carbon::parse($trigger->created_at),
                user: $user,
            );
        }

        return $entries;
    }

    /**
     * A single activitylog entry can contain multiple field changes.
     * We emit a separate changelog entry for each field so each
     * gets its own translatable enum type.
     *
     * @return AchievementChangelogEntryData[]
     */
    private function transformActivityToEntries(Activity $activity): array
    {
        $user = $activity->causer instanceof User
            ? $this->buildUserData($activity->causer)
            : null;

        // Lifecycle events map directly to a single changelog entry.
        $lifecycleType = match ($activity->event) {
            'created' => AchievementChangelogEntryType::Created,
            'deleted' => AchievementChangelogEntryType::Deleted,
            'restored' => AchievementChangelogEntryType::Restored,
            default => null,
        };

        if ($lifecycleType !== null) {
            return [new AchievementChangelogEntryData(
                type: $lifecycleType,
                createdAt: Carbon::parse($activity->created_at),
                user: $user,
            )];
        }

        // For 'updated' events, emit one entry per changed field.
        $properties = $activity->properties->toArray();
        $newValues = $properties['attributes'] ?? [];
        $oldValues = $properties['old'] ?? [];

        if (empty($newValues) && empty($oldValues)) {
            return [];
        }

        $entries = [];
        $changedFields = array_unique(array_merge(array_keys($newValues), array_keys($oldValues)));

        foreach ($changedFields as $rawField) {
            $normalizedField = self::FIELD_NORMALIZE_MAP[$rawField] ?? $rawField;
            $oldVal = isset($oldValues[$rawField]) ? $this->valueToString($oldValues[$rawField]) : null;
            $newVal = isset($newValues[$rawField]) ? $this->valueToString($newValues[$rawField]) : null;

            $entryType = $this->resolveEntryTypeForField($normalizedField, $oldVal, $newVal);
            if ($entryType === null) {
                continue;
            }

            $fieldChanges = [];
            if ($this->shouldShowFieldChanges($normalizedField)) {
                $fieldChanges[] = new ChangelogFieldChangeData(
                    oldValue: $this->formatFieldValue($normalizedField, $oldVal),
                    newValue: $this->formatFieldValue($normalizedField, $newVal),
                );
            }

            $entries[] = new AchievementChangelogEntryData(
                type: $entryType,
                createdAt: Carbon::parse($activity->created_at),
                user: $user,
                fieldChanges: $fieldChanges,
            );
        }

        return $entries;
    }

    private function resolveEntryTypeForField(
        string $field,
        ?string $oldValue,
        ?string $newValue,
    ): ?AchievementChangelogEntryType {
        if ($field === 'is_promoted') {
            // '1' is the modern boolean. '3' is the legacy Flags column
            // value for OfficialCore (5 was Unofficial).
            $isPromoted = $newValue === '1' || $newValue === '3';

            return $isPromoted
                ? AchievementChangelogEntryType::Promoted
                : AchievementChangelogEntryType::Demoted;
        }

        if ($field === 'type') {
            if ($newValue === null || $newValue === '') {
                return AchievementChangelogEntryType::TypeRemoved;
            }

            return ($oldValue === null || $oldValue === '')
                ? AchievementChangelogEntryType::TypeSet
                : AchievementChangelogEntryType::TypeChanged;
        }

        return self::FIELD_TO_ENTRY_TYPE[$field] ?? null;
    }

    /**
     * Activitylog properties can be arrays or objects, so we normalize them to strings for display.
     */
    private function valueToString(mixed $value): string
    {
        if (is_array($value)) {
            return json_encode($value) ?: '';
        }

        return (string) $value;
    }

    private function shouldShowFieldChanges(string $field): bool
    {
        return in_array($field, ['description', 'title', 'points', 'type', 'game_id'], true);
    }

    private function formatFieldValue(string $field, ?string $value): ?string
    {
        if ($value === null || $value === '') {
            return $value;
        }

        return match ($field) {
            'type' => self::ACHIEVEMENT_TYPE_LABELS[$value] ?? $value,
            'game_id' => $this->resolveGameTitle((int) $value),
            default => $value,
        };
    }

    private function resolveGameTitle(int $gameId): string
    {
        return Game::find($gameId)?->title ?? "Game #{$gameId}";
    }

    /**
     * @param Collection<int, Comment> $preCutoffComments
     * @return AchievementChangelogEntryData[]
     */
    private function getLegacyCommentEntries(Collection $preCutoffComments): array
    {
        $comments = $preCutoffComments->take(self::MAX_ENTRIES);

        $this->preResolveUsersFromComments($comments);

        $entries = [];
        foreach ($comments as $comment) {
            array_push($entries, ...$this->parseLegacyComment($comment));
        }

        return $entries;
    }

    /**
     * A single legacy comment can describe multiple field changes
     * (eg: "edited this achievement's description, logic.").
     *
     * We emit a separate changelog entry for each recognized field.
     *
     * @return AchievementChangelogEntryData[]
     */
    private function parseLegacyComment(Comment $comment): array
    {
        // Older legacy comments wrapped the display name in quotes (eg: "meleu" promoted ...).
        // Strip leading quotes so the regex captures the bare name.
        $body = preg_replace('/^"([^"]+)"/', '$1', $comment->body);
        $createdAt = Carbon::parse($comment->created_at);

        if (preg_match('/^(.+?) uploaded this achievement\.$/', $body, $matches)) {
            return [new AchievementChangelogEntryData(
                type: AchievementChangelogEntryType::Created,
                createdAt: $createdAt,
                user: $this->resolveUserByDisplayName($matches[1]),
            )];
        }

        if (preg_match('/^(.+?) promoted this achievement to the Core set\.$/', $body, $matches)) {
            return [new AchievementChangelogEntryData(
                type: AchievementChangelogEntryType::Promoted,
                createdAt: $createdAt,
                user: $this->resolveUserByDisplayName($matches[1]),
            )];
        }

        if (preg_match('/^(.+?) demoted this achievement to Unofficial\.$/', $body, $matches)) {
            return [new AchievementChangelogEntryData(
                type: AchievementChangelogEntryType::Demoted,
                createdAt: $createdAt,
                user: $this->resolveUserByDisplayName($matches[1]),
            )];
        }

        if (preg_match('/^(.+?) edited this achievement\'s (.+)\.$/', $body, $matches)) {
            $user = $this->resolveUserByDisplayName($matches[1]);
            $fields = explode(', ', $matches[2]);

            $entries = [];
            foreach ($fields as $fieldText) {
                $fieldText = strtolower(trim($fieldText));
                $entryType = self::LEGACY_FIELD_TO_ENTRY_TYPE[$fieldText] ?? AchievementChangelogEntryType::Edited;

                $entries[] = new AchievementChangelogEntryData(
                    type: $entryType,
                    createdAt: $createdAt,
                    user: $user,
                );
            }

            return $entries;
        }

        if (preg_match('/^(.+?) edited this achievement\.$/', $body, $matches)) {
            return [new AchievementChangelogEntryData(
                type: AchievementChangelogEntryType::Edited,
                createdAt: $createdAt,
                user: $this->resolveUserByDisplayName($matches[1]),
            )];
        }

        if (preg_match('/^(.+?) set this achievement\'s type to (.+)\.$/', $body, $matches)) {
            return [new AchievementChangelogEntryData(
                type: AchievementChangelogEntryType::TypeSet,
                createdAt: $createdAt,
                user: $this->resolveUserByDisplayName($matches[1]),
                fieldChanges: [new ChangelogFieldChangeData(newValue: $matches[2])],
            )];
        }

        if (preg_match('/^(.+?) removed this achievement\'s type\.$/', $body, $matches)) {
            return [new AchievementChangelogEntryData(
                type: AchievementChangelogEntryType::TypeRemoved,
                createdAt: $createdAt,
                user: $this->resolveUserByDisplayName($matches[1]),
            )];
        }

        if (preg_match('/^(.+?) set this achievement\'s embed URL\.$/', $body, $matches)) {
            return [new AchievementChangelogEntryData(
                type: AchievementChangelogEntryType::EmbedUrlUpdated,
                createdAt: $createdAt,
                user: $this->resolveUserByDisplayName($matches[1]),
            )];
        }

        // Fall through for unrecognized comment formats.
        return [new AchievementChangelogEntryData(
            type: AchievementChangelogEntryType::Edited,
            createdAt: $createdAt,
        )];
    }

    /**
     * Batch-extract and pre-resolve display names from comment bodies
     * so that parseLegacyComment can look them up without N+1 queries.
     *
     * @param Collection<int, Comment> $comments
     */
    private function preResolveUsersFromComments(Collection $comments): void
    {
        $displayNames = $comments
            ->map(fn (Comment $c) => $this->extractDisplayNameFromComment($c->body))
            ->filter()
            ->unique()
            ->values();

        // Filter out names already in the cache.
        $uncached = $displayNames->filter(fn ($name) => !array_key_exists($name, $this->userCache));
        if ($uncached->isEmpty()) {
            return;
        }

        // Legacy comments used username before 2025, then ultimately switched
        // to using display_name. Therefore, we need to check both columns.
        $names = $uncached->all();
        $users = User::withTrashed()
            ->where(fn ($query) => $query->whereIn('display_name', $names)->orWhereIn('username', $names))
            ->get();

        foreach ($uncached as $name) {
            $this->userCache[$name] = $users->first(
                fn (User $u) => $u->display_name === $name || $u->username === $name
            );
        }
    }

    private function extractDisplayNameFromComment(string $body): ?string
    {
        if (preg_match('/^(.+?) (?:uploaded|promoted|demoted|edited|set|removed)/', $body, $matches)) {
            return trim($matches[1], '"');
        }

        return null;
    }

    /**
     * Legacy comments embed the display_name as free-form unstructured
     * text, so we need to look up the actual user record to get a proper
     * UserData DTO instance.
     */
    private function resolveUserByDisplayName(string $displayName): ?UserData
    {
        if (!array_key_exists($displayName, $this->userCache)) {
            $this->userCache[$displayName] = User::whereName($displayName)->withTrashed()->first();
        }

        $user = $this->userCache[$displayName];

        return $user ? $this->buildUserData($user) : null;
    }

    private function buildUserData(User $user): UserData
    {
        return UserData::fromUser($user)->include('deletedAt', 'isBanned');
    }
}

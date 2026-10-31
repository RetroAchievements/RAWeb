<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Community\Enums\CommentableType;
use App\Community\Enums\TicketResolution;
use App\Data\UserData;
use App\Models\Comment;
use App\Models\Ticket;
use App\Models\User;
use App\Platform\Data\TicketChangelogEntryData;
use App\Platform\Enums\TicketChangelogEntryType;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\Models\Activity;

class BuildTicketChangelogAction
{
    private const LEGACY_COMMENT_PATTERNS = [
        '/^Ticket resolved as fixed by (.+)\.$/s' => TicketChangelogEntryType::Resolved,
        '/^Resolved as fixed by (.+)$/s' => TicketChangelogEntryType::Resolved,
        '/^Resolved as removed by (.+)$/s' => TicketChangelogEntryType::Closed,
        '/^Ticket reassigned to reporter by (.+)\.$/s' => TicketChangelogEntryType::Requested,
        '/^Ticket reassigned to author by (.+)\.$/s' => TicketChangelogEntryType::Returned,
        '/^Ticket approved by (.+)\.$/s' => TicketChangelogEntryType::Approved,
        '/^Ticket reopened by (.+)\.$/s' => TicketChangelogEntryType::Reopened,
    ];

    /** @var array<string, User|null> Pre-resolved user lookup to avoid N+1 queries on legacy comments. */
    private array $userCache = [];

    /**
     * @return TicketChangelogEntryData[]
     */
    public function execute(Ticket $ticket): array
    {
        $activities = Activity::query()
            ->where('subject_type', 'ticket')
            ->where('subject_id', $ticket->id)
            ->whereIn('event', ['updated', Ticket::REPORTER_REMINDED_EVENT])
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        $allSystemComments = Comment::query()
            ->where('commentable_type', CommentableType::Ticket)
            ->where('commentable_id', $ticket->id)
            ->where('user_id', Comment::SYSTEM_USER_ID)
            ->when(
                $activities->first(),
                fn ($query, Activity $firstActivity) => $query->where('created_at', '<', $firstActivity->created_at),
            )
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        return [
            ...$this->getLegacyCommentEntries($allSystemComments),
            ...$this->getActivitylogEntries($activities),
        ];
    }

    /**
     * @param EloquentCollection<int, Activity> $activities
     * @return TicketChangelogEntryData[]
     */
    private function getActivitylogEntries(EloquentCollection $activities): array
    {
        // Manually load causers including soft-deleted users, since
        // the default morphTo causer relationship excludes them.
        $causerIds = $activities->pluck('causer_id')->filter()->unique()->values();
        $causers = User::withTrashed()->whereIn('id', $causerIds)->get()->keyBy('id');

        $entries = [];
        foreach ($activities as $activity) {
            $entry = $this->transformActivityToEntry($activity, $causers->get($activity->causer_id));
            if ($entry !== null) {
                $entries[] = $entry;
            }
        }

        return $entries;
    }

    private function mapStateChangeToEntryType(?string $incomingState, ?string $previousState): ?TicketChangelogEntryType
    {
        return match ($incomingState) {
            'resolved' => TicketChangelogEntryType::Resolved,
            'closed' => TicketChangelogEntryType::Closed,
            'request' => TicketChangelogEntryType::Requested,
            'open' => match ($previousState) {
                'request' => TicketChangelogEntryType::Returned,
                'quarantined' => TicketChangelogEntryType::Approved,
                default => TicketChangelogEntryType::Reopened,
            },
            default => null,
        };
    }

    private function transformActivityToEntry(Activity $activity, ?User $causer): ?TicketChangelogEntryData
    {
        $properties = $activity->properties->toArray();
        $newValues = $properties['attributes'] ?? [];
        $oldValues = $properties['old'] ?? [];

        $type = $activity->event === Ticket::REPORTER_REMINDED_EVENT
            ? TicketChangelogEntryType::Reminded
            : $this->mapStateChangeToEntryType($newValues['state'] ?? null, $oldValues['state'] ?? null);

        if ($type === null) {
            return null;
        }

        return new TicketChangelogEntryData(
            type: $type,
            createdAt: Carbon::parse($activity->created_at),
            user: $causer ? $this->buildUserData($causer) : null,
            resolution: isset($newValues['resolution']) ? TicketResolution::from($newValues['resolution']) : null,
        );
    }

    /**
     * @param EloquentCollection<int, Comment> $comments
     * @return TicketChangelogEntryData[]
     */
    private function getLegacyCommentEntries(EloquentCollection $comments): array
    {
        $parsedComments = [];
        foreach ($comments as $comment) {
            $parsed = $this->parseLegacyComment($comment->body);
            if ($parsed !== null) {
                $parsedComments[] = [...$parsed, 'createdAt' => Carbon::parse($comment->created_at)];
            }
        }

        $this->preResolveUsers(array_column($parsedComments, 'actorName'));

        return array_map(
            function (array $parsedComment): TicketChangelogEntryData {
                $user = $this->userCache[$parsedComment['actorName']];

                return new TicketChangelogEntryData(
                    type: $parsedComment['type'],
                    createdAt: $parsedComment['createdAt'],
                    user: $user ? $this->buildUserData($user) : null,
                    resolution: $parsedComment['resolution'],
                );
            },
            $parsedComments,
        );
    }

    /**
     * @return array{actorName: string, type: TicketChangelogEntryType, resolution: ?TicketResolution}|null
     */
    private function parseLegacyComment(string $body): ?array
    {
        if (preg_match('/^Ticket closed by (.+)\. Reason: "(.+)"\.$/s', $body, $matches)) {
            $resolution = TicketResolution::fromCloseReasonText($matches[2]);

            return $resolution
                ? ['actorName' => $matches[1], 'type' => TicketChangelogEntryType::Closed, 'resolution' => $resolution]
                : null;
        }

        foreach (self::LEGACY_COMMENT_PATTERNS as $pattern => $type) {
            if (preg_match($pattern, $body, $matches)) {
                return [
                    'actorName' => $matches[1],
                    'type' => $type,
                    'resolution' => $type === TicketChangelogEntryType::Resolved ? TicketResolution::Fixed : null,
                ];
            }
        }

        return null;
    }

    /**
     * Before 2025, comments used username values. They later changed to using display_name instead.
     * Because of this, we need to check both fields in the users table.
     *
     * @param string[] $names
     */
    private function preResolveUsers(array $names): void
    {
        $names = array_values(array_unique($names));
        if (!$names) {
            return;
        }

        $users = User::withTrashed()
            ->where(fn ($query) => $query->whereIn('display_name', $names)->orWhereIn('username', $names))
            ->get();

        foreach ($names as $name) {
            $this->userCache[$name] = $users->first(
                fn (User $u) => $u->display_name === $name || $u->username === $name
            );
        }
    }

    private function buildUserData(User $user): UserData
    {
        return UserData::fromUser($user)->include('isGone');
    }
}

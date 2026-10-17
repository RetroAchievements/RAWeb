<?php

declare(strict_types=1);

use App\Community\Enums\CommentableType;
use App\Community\Enums\TicketResolution;
use App\Models\Achievement;
use App\Models\Comment;
use App\Models\Game;
use App\Models\Ticket;
use App\Models\User;
use App\Platform\Actions\BuildTicketChangelogAction;
use App\Platform\Data\TicketChangelogEntryData;
use App\Platform\Enums\TicketChangelogEntryType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;

uses(RefreshDatabase::class);

/**
 * The `Ticket` model uses Spatie's `LogsActivity` trait, which auto-creates
 * activity entries on model events. This helper function clears those entries
 * so our tests can control exactly which activities exist.
 */
function createTicketWithoutLog(array $attributes = []): Ticket
{
    $game = Game::factory()->create();
    $achievement = Achievement::factory()->create(['game_id' => $game->id]);
    $ticket = Ticket::factory()->forAchievement($achievement)->create($attributes);
    Activity::query()->delete();

    return $ticket;
}

function createTicketSystemUser(): User
{
    return User::factory()->create(['id' => Comment::SYSTEM_USER_ID]);
}

function createTicketLegacyComment(Ticket $ticket, string $body, string $date): Comment
{
    return Comment::factory()->create([
        'commentable_type' => CommentableType::Ticket,
        'commentable_id' => $ticket->id,
        'user_id' => Comment::SYSTEM_USER_ID,
        'body' => $body,
        'created_at' => $date,
    ]);
}

function createTicketActivity(
    Ticket $ticket,
    string $event,
    string $date,
    ?User $causer = null,
    array $attributes = [],
    array $old = [],
): Activity {
    $properties = [];
    if (!empty($attributes)) {
        $properties['attributes'] = $attributes;
    }
    if (!empty($old)) {
        $properties['old'] = $old;
    }

    return Activity::create([
        'subject_type' => 'ticket',
        'subject_id' => $ticket->id,
        'event' => $event,
        'description' => $event,
        'causer_type' => $causer ? 'user' : null,
        'causer_id' => $causer?->id,
        'properties' => $properties,
        'created_at' => $date,
        'updated_at' => $date,
    ]);
}

/**
 * @return TicketChangelogEntryData[]
 */
function ticketEntriesOfType(array $result, TicketChangelogEntryType $type): array
{
    return array_values(array_filter(
        $result,
        fn (TicketChangelogEntryData $e) => $e->type === $type,
    ));
}

describe('Activitylog Entries', function () {
    it('maps state changes to the correct entry type', function (
        array $old,
        array $attributes,
        TicketChangelogEntryType $expectedType,
        ?TicketResolution $expectedResolution,
    ) {
        // Arrange
        $user = User::factory()->create();
        $ticket = createTicketWithoutLog();

        createTicketActivity($ticket, 'updated', '2026-10-01 12:00:00', $user,
            attributes: $attributes,
            old: $old,
        );

        // Act
        $result = (new BuildTicketChangelogAction())->execute($ticket);

        // Assert
        $entries = ticketEntriesOfType($result, $expectedType);
        expect($entries)->toHaveCount(1);
        expect($entries[0]->resolution)->toEqual($expectedResolution);
        expect($entries[0]->user->displayName)->toEqual($user->display_name);
    })->with([
        'resolved' => [['state' => 'open', 'resolution' => null], ['state' => 'resolved', 'resolution' => 'fixed'], TicketChangelogEntryType::Resolved, TicketResolution::Fixed],
        'closed' => [['state' => 'open', 'resolution' => null], ['state' => 'closed', 'resolution' => 'wrong_rom'], TicketChangelogEntryType::Closed, TicketResolution::WrongRom],
        'requested' => [['state' => 'open'], ['state' => 'request'], TicketChangelogEntryType::Requested, null],
        'returned' => [['state' => 'request'], ['state' => 'open'], TicketChangelogEntryType::Returned, null],
        'approved' => [['state' => 'quarantined'], ['state' => 'open'], TicketChangelogEntryType::Approved, null],
        'reopened from closed' => [['state' => 'closed', 'resolution' => 'other'], ['state' => 'open', 'resolution' => null], TicketChangelogEntryType::Reopened, null],
        'reopened from resolved' => [['state' => 'resolved', 'resolution' => 'fixed'], ['state' => 'open', 'resolution' => null], TicketChangelogEntryType::Reopened, null],
    ]);

    it('ignores lifecycle events', function (string $event) {
        // Arrange
        $user = User::factory()->create();
        $ticket = createTicketWithoutLog();

        createTicketActivity($ticket, $event, '2026-10-01 12:00:00', $user,
            attributes: ['state' => 'open'],
        );

        // Act
        $result = (new BuildTicketChangelogAction())->execute($ticket);

        // Assert
        expect($result)->toEqual([]);
    })->with(['created', 'deleted', 'restored']);
});

describe('Legacy Comment Entries', function () {
    it('maps comment patterns to the correct entry type', function (
        string $body,
        TicketChangelogEntryType $expectedType,
        ?TicketResolution $expectedResolution,
    ) {
        // Arrange
        createTicketSystemUser();
        User::factory()->create(['display_name' => 'Scott']);
        $ticket = createTicketWithoutLog();

        createTicketLegacyComment($ticket, $body, '2023-06-15 12:00:00');

        // Act
        $result = (new BuildTicketChangelogAction())->execute($ticket);

        // Assert
        $entries = ticketEntriesOfType($result, $expectedType);
        expect($entries)->toHaveCount(1);
        expect($entries[0]->resolution)->toEqual($expectedResolution);
        expect($entries[0]->user->displayName)->toEqual('Scott');
    })->with([
        'resolved' => ['Ticket resolved as fixed by Scott.', TicketChangelogEntryType::Resolved, TicketResolution::Fixed],
        'resolved, 2017 wording' => ['Resolved as fixed by Scott', TicketChangelogEntryType::Resolved, TicketResolution::Fixed],
        'removed, 2017 wording' => ['Resolved as removed by Scott', TicketChangelogEntryType::Closed, null],
        'requested' => ['Ticket reassigned to reporter by Scott.', TicketChangelogEntryType::Requested, null],
        'returned' => ['Ticket reassigned to author by Scott.', TicketChangelogEntryType::Returned, null],
        'approved' => ['Ticket approved by Scott.', TicketChangelogEntryType::Approved, null],
        'reopened' => ['Ticket reopened by Scott.', TicketChangelogEntryType::Reopened, null],
    ]);

    it('maps every close reason to its resolution', function (TicketResolution $resolution) {
        // Arrange
        createTicketSystemUser();
        User::factory()->create(['display_name' => 'Scott']);
        $ticket = createTicketWithoutLog();

        createTicketLegacyComment($ticket, $resolution->closeCommentBody('Scott'), '2023-06-15 12:00:00');

        // Act
        $result = (new BuildTicketChangelogAction())->execute($ticket);

        // Assert
        $entries = ticketEntriesOfType($result, TicketChangelogEntryType::Closed);
        expect($entries)->toHaveCount(1);
        expect($entries[0]->resolution)->toEqual($resolution);
    })->with(array_filter(TicketResolution::cases(), fn (TicketResolution $r) => $r !== TicketResolution::Fixed));

    it('parses usernames from older legacy comments', function () {
        // Arrange
        createTicketSystemUser();
        User::factory()->create(['username' => 'oldname', 'display_name' => 'NewName']);
        $ticket = createTicketWithoutLog();

        createTicketLegacyComment($ticket, 'Resolved as fixed by oldname', '2017-08-09 12:00:00');

        // Act
        $result = (new BuildTicketChangelogAction())->execute($ticket);

        // Assert
        $entries = ticketEntriesOfType($result, TicketChangelogEntryType::Resolved);
        expect($entries)->toHaveCount(1);
        expect($entries[0]->user->displayName)->toEqual('NewName');
    });

    it('skips unrecognized comment formats', function () {
        // Arrange
        createTicketSystemUser();
        $ticket = createTicketWithoutLog();

        createTicketLegacyComment($ticket, 'Something completely unexpected happened.', '2023-06-15 12:00:00');

        // Act
        $result = (new BuildTicketChangelogAction())->execute($ticket);

        // Assert
        expect($result)->toEqual([]);
    });

    it('does not include comments from after the first activitylog entry as legacy entries', function () {
        // Arrange
        createTicketSystemUser();
        $user = User::factory()->create(['display_name' => 'Scott']);
        $ticket = createTicketWithoutLog();

        createTicketActivity($ticket, 'updated', '2026-10-01 12:00:00', $user,
            attributes: ['state' => 'closed', 'resolution' => 'wrong_rom'],
            old: ['state' => 'open', 'resolution' => null],
        );

        // ... the Server comment that the same state change wrote for the legacy ticket page ...
        createTicketLegacyComment($ticket, 'Ticket closed by Scott. Reason: "Wrong ROM".', '2026-10-01 12:00:00');

        // Act
        $result = (new BuildTicketChangelogAction())->execute($ticket);

        // Assert
        expect($result)->toHaveCount(1);
        expect($result[0]->type)->toEqual(TicketChangelogEntryType::Closed);
    });
});

describe('Merging and Sorting', function () {
    it('returns entries from both sources ascending by date', function () {
        // Arrange
        createTicketSystemUser();
        $user = User::factory()->create(['display_name' => 'Scott']);
        $ticket = createTicketWithoutLog();

        createTicketLegacyComment($ticket, 'Ticket reassigned to author by Scott.', '2023-01-16 12:00:00');
        createTicketLegacyComment($ticket, 'Ticket reassigned to reporter by Scott.', '2023-01-15 12:00:00');

        createTicketActivity($ticket, 'updated', '2026-10-01 12:00:00', $user,
            attributes: ['state' => 'resolved', 'resolution' => 'fixed'],
            old: ['state' => 'open', 'resolution' => null],
        );

        // Act
        $result = (new BuildTicketChangelogAction())->execute($ticket);

        // Assert
        expect($result[0]->type)->toEqual(TicketChangelogEntryType::Requested);
        expect($result[1]->type)->toEqual(TicketChangelogEntryType::Returned);
        expect($result[2]->type)->toEqual(TicketChangelogEntryType::Resolved);
    });
});

describe('Edge Cases', function () {
    it('returns no entries when there is no changelog data', function () {
        // Arrange
        $ticket = createTicketWithoutLog();

        // Act
        $result = (new BuildTicketChangelogAction())->execute($ticket);

        // Assert
        expect($result)->toEqual([]);
    });

    it('correctly resolves soft deleted users as activitylog causers', function () {
        // Arrange
        $user = User::factory()->create();
        $ticket = createTicketWithoutLog();

        createTicketActivity($ticket, 'updated', '2026-10-01 12:00:00', $user,
            attributes: ['state' => 'request'],
            old: ['state' => 'open'],
        );

        $user->delete();

        // Act
        $result = (new BuildTicketChangelogAction())->execute($ticket);

        // Assert
        $entries = ticketEntriesOfType($result, TicketChangelogEntryType::Requested);
        expect($entries)->toHaveCount(1);
        expect($entries[0]->user)->not->toBeNull();
        expect($entries[0]->user->displayName)->toEqual($user->display_name);
    });

    it('correctly resolves soft deleted users in a legacy comments', function () {
        // Arrange
        createTicketSystemUser();
        $user = User::factory()->create(['display_name' => 'Scott']);
        $ticket = createTicketWithoutLog();

        createTicketLegacyComment($ticket, 'Ticket reassigned to reporter by Scott.', '2023-06-15 12:00:00');

        $user->delete();

        // Act
        $result = (new BuildTicketChangelogAction())->execute($ticket);

        // Assert
        $entries = ticketEntriesOfType($result, TicketChangelogEntryType::Requested);
        expect($entries)->toHaveCount(1);
        expect($entries[0]->user)->not->toBeNull();
        expect($entries[0]->user->displayName)->toEqual('Scott');
    });
});

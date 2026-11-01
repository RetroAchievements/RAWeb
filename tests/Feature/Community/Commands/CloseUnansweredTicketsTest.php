<?php

declare(strict_types=1);

use App\Community\Commands\CloseUnansweredTickets;
use App\Community\Enums\CommentableType;
use App\Community\Enums\TicketResolution;
use App\Community\Enums\TicketState;
use App\Enums\UserPreference;
use App\Models\Achievement;
use App\Models\Comment;
use App\Models\Game;
use App\Models\System;
use App\Models\Ticket;
use App\Models\User;
use App\Notifications\Ticket\TicketReplyReminderNotification;
use App\Notifications\Ticket\TicketStatusUpdatedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

beforeEach(function () {
    Notification::fake();

    User::factory()->create(['id' => Comment::SYSTEM_USER_ID, 'username' => 'Server', 'display_name' => 'Server']);
});

function addTicketComment(Ticket $ticket, int $authorId, string $body, int $daysOld): void
{
    Comment::create([
        'commentable_type' => CommentableType::Ticket,
        'commentable_id' => $ticket->id,
        'body' => $body,
        'user_id' => $authorId,
        'created_at' => now()->subDays($daysOld),
    ]);
}

function addTicketReminder(Ticket $ticket, int $daysOld): void
{
    activity()
        ->causedBy(User::find(Comment::SYSTEM_USER_ID))
        ->performedOn($ticket)
        ->event(Ticket::REPORTER_REMINDED_EVENT)
        ->createdAt(now()->subDays($daysOld))
        ->log(Ticket::REPORTER_REMINDED_EVENT);
}

function countTicketReminders(Ticket $ticket): int
{
    return $ticket->auditLog()->where('event', Ticket::REPORTER_REMINDED_EVENT)->count();
}

it('given a week or more of silence from the reporter, nudges them', function (int $daysSilent) {
    // Arrange
    $ticketGame = Game::factory()->create(['system_id' => System::factory()]);
    $ticketAchievement = Achievement::factory()->promoted()->create(['game_id' => $ticketGame->id]);
    $reporterUser = User::factory()->create(['preferences_bitfield' => 1 << UserPreference::EmailOn_TicketActivity]);
    $ticket = Ticket::factory()->forAchievement($ticketAchievement)->request()->create(['reporter_id' => $reporterUser->id]);

    addTicketComment($ticket, Comment::SYSTEM_USER_ID, 'Ticket reassigned to the reporter by Scott.', $daysSilent);

    // Act
    $this->artisan(CloseUnansweredTickets::class)->assertExitCode(0);

    // Assert
    expect($ticket->refresh()->state)->toEqual(TicketState::Request);
    expect(countTicketReminders($ticket))->toEqual(1);
    expect($ticket->comments()->count())->toEqual(1);
    Notification::assertSentTo($reporterUser, TicketReplyReminderNotification::class);
})->with([
    'exactly a week' => [7],
    'long past the threshold on the first ever run' => [240],
]);

it('given a third-party left a comment after the ticket status was changed to Request, quotes the comment that came before the state change', function () {
    // Arrange
    $ticketGame = Game::factory()->create(['system_id' => System::factory()]);
    $ticketAchievement = Achievement::factory()->promoted()->create(['game_id' => $ticketGame->id]);
    $reporterUser = User::factory()->create(['preferences_bitfield' => 1 << UserPreference::EmailOn_TicketActivity]);
    $developerUser = User::factory()->create();
    $bystanderUser = User::factory()->create();
    $ticket = Ticket::factory()->forAchievement($ticketAchievement)->request()->create(['reporter_id' => $reporterUser->id]);

    addTicketComment($ticket, $developerUser->id, 'Did you do anything different on your successful attempt?', 9);
    addTicketComment($ticket, Comment::SYSTEM_USER_ID, 'Ticket reassigned to reporter by Scott.', 9);
    addTicketComment($ticket, $bystanderUser->id, 'Any advice on this? I have the same problem.', 8);

    // Act
    $this->artisan(CloseUnansweredTickets::class)->assertExitCode(0);

    // Assert
    Notification::assertSentTo(
        $reporterUser,
        TicketReplyReminderNotification::class,
        fn (TicketReplyReminderNotification $notification) => $notification->toMail($reporterUser)->viewData['question']->user_id === $developerUser->id,
    );
});

it('given the nudge went out 7 days ago and there is no reply, closes the ticket', function () {
    // Arrange
    $ticketGame = Game::factory()->create(['system_id' => System::factory()]);
    $ticketAchievement = Achievement::factory()->promoted()->create(['game_id' => $ticketGame->id]);
    $reporterUser = User::factory()->create(['preferences_bitfield' => 1 << UserPreference::EmailOn_TicketActivity]);
    $ticket = Ticket::factory()->forAchievement($ticketAchievement)->request()->create(['reporter_id' => $reporterUser->id]);

    addTicketComment($ticket, Comment::SYSTEM_USER_ID, 'Ticket reassigned to the reporter by Scott.', 15);
    addTicketReminder($ticket, 7);

    // Act
    $this->artisan(CloseUnansweredTickets::class)->assertExitCode(0);

    // Assert
    $ticket->refresh();
    expect($ticket->state)->toEqual(TicketState::Closed);
    expect($ticket->resolution)->toEqual(TicketResolution::NoReplyFromReporter);
    expect($ticket->resolver_id)->toEqual(Comment::SYSTEM_USER_ID);
    expect($ticket->comments()->latest('id')->first()->body)->toEqual('Ticket closed by Server. Reason: "No reply from reporter".');
    Notification::assertSentTo($reporterUser, TicketStatusUpdatedNotification::class);
});

it('given the reporter still has time to reply, does not close the ticket', function (int $commentDaysOld, ?int $reminderDaysOld) {
    // Arrange
    $ticketGame = Game::factory()->create(['system_id' => System::factory()]);
    $ticketAchievement = Achievement::factory()->promoted()->create(['game_id' => $ticketGame->id]);
    $reporterUser = User::factory()->create(['preferences_bitfield' => 1 << UserPreference::EmailOn_TicketActivity]);
    $ticket = Ticket::factory()->forAchievement($ticketAchievement)->request()->create(['reporter_id' => $reporterUser->id]);

    addTicketComment($ticket, Comment::SYSTEM_USER_ID, 'Ticket reassigned to the reporter by Scott.', $commentDaysOld);

    if ($reminderDaysOld !== null) {
        addTicketReminder($ticket, $reminderDaysOld);
    }

    // Act
    $this->artisan(CloseUnansweredTickets::class)->assertExitCode(0);

    // Assert
    expect($ticket->refresh()->state)->toEqual(TicketState::Request);
    expect(countTicketReminders($ticket))->toEqual($reminderDaysOld === null ? 0 : 1);
    Notification::assertNothingSent();
})->with([
    'no nudge yet' => [6, null],
    'nudged, but the closing wait is still running' => [13, 6],
]);

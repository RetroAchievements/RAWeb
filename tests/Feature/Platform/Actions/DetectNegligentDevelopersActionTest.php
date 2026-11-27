<?php

declare(strict_types=1);

use App\Community\Enums\CommentableType;
use App\Community\Enums\TicketState;
use App\Models\Achievement;
use App\Models\Comment;
use App\Models\Game;
use App\Models\Role;
use App\Models\System;
use App\Models\Ticket;
use App\Models\User;
use App\Platform\Actions\DetectNegligentDevelopersAction;
use App\Support\Alerts\Jobs\SendAlertWebhookJob;
use App\Support\Alerts\TicketNegligenceAlert;
use Database\Seeders\RolesTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Spatie\Activitylog\Models\Activity;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RolesTableSeeder::class);

    Cache::flush();
    Queue::fake();
    Carbon::setTestNow(Carbon::parse('2026-11-20 12:00:00'));

    config(['services.discord.alerts_webhook.ticket_negligence' => 'https://discord.com/api/webhooks/test']);
});

afterEach(function (): void {
    Carbon::setTestNow();
});

function createDeveloperForNegligenceTest(array $attributes = []): User
{
    /** @var User $user */
    $user = User::factory()->create($attributes);
    $user->assignRole(Role::DEVELOPER);

    return $user;
}

function createAchievementForNegligenceTest(User $author): Achievement
{
    $system = System::factory()->create();
    $game = Game::factory()->create(['system_id' => $system->id]);

    /** @var Achievement $achievement */
    $achievement = Achievement::factory()->create(['game_id' => $game->id, 'user_id' => $author->id]);

    return $achievement;
}

function createTicketForNegligenceTest(Achievement $achievement, User $assignee, int $ageInDays, TicketState $state = TicketState::Open): Ticket
{
    /** @var Ticket $ticket */
    $ticket = Ticket::factory()->forAchievement($achievement)->create([
        'assignee_id' => $assignee->id,
        'state' => $state,
        'created_at' => Carbon::now()->subDays($ageInDays),
    ]);

    return $ticket;
}

function createTicketCommentForNegligenceTest(Ticket $ticket, User $author, int $ageInDays): void
{
    Comment::create([
        'user_id' => $author->id,
        'commentable_type' => CommentableType::Ticket,
        'commentable_id' => $ticket->id,
        'body' => 'Still looking into this one.',
        'created_at' => Carbon::now()->subDays($ageInDays),
    ]);
}

/**
 * @return array<int, array<string, mixed>>
 */
function getPostedNegligenceEntries(): array
{
    $entries = [];

    Queue::assertPushed(SendAlertWebhookJob::class, function (SendAlertWebhookJob $job) use (&$entries): bool {
        if ($job->alert instanceof TicketNegligenceAlert) {
            $entries = array_merge($entries, $job->alert->entries);
        }

        return true;
    });

    return $entries;
}

it('given a developer with a ticket unaddressed for 90 days, posts', function (): void {
    // Arrange
    $developer = createDeveloperForNegligenceTest(['display_name' => 'SlowDev']);
    $achievement = createAchievementForNegligenceTest($developer);
    createTicketForNegligenceTest($achievement, $developer, ageInDays: 95);
    createTicketForNegligenceTest($achievement, $developer, ageInDays: 40);
    createTicketForNegligenceTest($achievement, $developer, ageInDays: 10);

    // Act
    $findingsCount = (new DetectNegligentDevelopersAction())->execute();

    // Assert
    expect($findingsCount)->toBe(1);
    expect(getPostedNegligenceEntries())->toBe([
        [
            'displayName' => 'SlowDev',
            'staleTicketCount' => 2,
        ],
    ]);
});

it('given developers with several unaddressed tickets over 30 days old, posts only the one with 8+', function (): void {
    // Arrange
    $eightTicketsDeveloper = createDeveloperForNegligenceTest(['display_name' => 'EightTickets']);
    $eightTicketsAchievement = createAchievementForNegligenceTest($eightTicketsDeveloper);
    foreach (range(1, 8) as $ignored) {
        createTicketForNegligenceTest($eightTicketsAchievement, $eightTicketsDeveloper, ageInDays: 40);
    }

    $sevenTicketsDeveloper = createDeveloperForNegligenceTest(['display_name' => 'SevenTickets']);
    $sevenTicketsAchievement = createAchievementForNegligenceTest($sevenTicketsDeveloper);
    foreach (range(1, 7) as $ignored) {
        createTicketForNegligenceTest($sevenTicketsAchievement, $sevenTicketsDeveloper, ageInDays: 40);
    }

    // Act
    $findingsCount = (new DetectNegligentDevelopersAction())->execute();

    // Assert
    expect($findingsCount)->toBe(1);
    expect(getPostedNegligenceEntries()[0]['displayName'])->toBe('EightTickets');
});

it('given only claimed, request, or recently commented tickets, does not post', function (): void {
    // Arrange
    $developer = createDeveloperForNegligenceTest(['display_name' => 'BusyDev']);
    $otherDeveloper = createDeveloperForNegligenceTest(['display_name' => 'OtherDev']);

    $claimedAchievement = createAchievementForNegligenceTest($otherDeveloper);
    createTicketForNegligenceTest($claimedAchievement, $developer, ageInDays: 95);

    $ownAchievement = createAchievementForNegligenceTest($developer);
    createTicketForNegligenceTest($ownAchievement, $developer, ageInDays: 95, state: TicketState::Request);

    $recentlyAddressedTicket = createTicketForNegligenceTest($ownAchievement, $developer, ageInDays: 95);
    createTicketCommentForNegligenceTest($recentlyAddressedTicket, $developer, ageInDays: 5);

    // Act
    $findingsCount = (new DetectNegligentDevelopersAction())->execute();

    // Assert
    expect($findingsCount)->toBe(0);
    Queue::assertNotPushed(SendAlertWebhookJob::class);
});

it('given a recent comment from someone other than the assignee, still considers the ticket unaddressed', function (): void {
    // Arrange
    $developer = createDeveloperForNegligenceTest(['display_name' => 'QuietDev']);
    $reporter = User::factory()->create();

    $achievement = createAchievementForNegligenceTest($developer);
    $ticket = createTicketForNegligenceTest($achievement, $developer, ageInDays: 95);
    createTicketCommentForNegligenceTest($ticket, $reporter, ageInDays: 1);
    createTicketCommentForNegligenceTest($ticket, $developer, ageInDays: 20);

    // Act
    $findingsCount = (new DetectNegligentDevelopersAction())->execute();

    // Assert
    expect($findingsCount)->toBe(1);
    expect(getPostedNegligenceEntries()[0]['displayName'])->toBe('QuietDev');
});

it('given a ticket that returned to open recently, does not post', function (): void {
    // Arrange
    $developer = createDeveloperForNegligenceTest();
    $achievement = createAchievementForNegligenceTest($developer);
    $ticket = createTicketForNegligenceTest($achievement, $developer, ageInDays: 95);
    createTicketCommentForNegligenceTest($ticket, $developer, ageInDays: 30);

    Activity::create([
        'log_name' => 'default',
        'description' => 'updated',
        'event' => 'updated',
        'subject_type' => $ticket->getMorphClass(),
        'subject_id' => $ticket->id,
        'properties' => ['old' => ['state' => TicketState::Request->value], 'attributes' => ['state' => TicketState::Open->value]],
        'created_at' => Carbon::now()->subDays(2),
    ]);

    // Act
    $findingsCount = (new DetectNegligentDevelopersAction())->execute();

    // Assert
    expect($findingsCount)->toBe(0);
    Queue::assertNotPushed(SendAlertWebhookJob::class);
});

<?php

declare(strict_types=1);

use App\Community\Enums\CommentableType;
use App\Community\Enums\TicketState;
use App\Models\Achievement;
use App\Models\AchievementAuthor;
use App\Models\AchievementSetClaim;
use App\Models\Comment;
use App\Models\Game;
use App\Models\Leaderboard;
use App\Models\MemoryNote;
use App\Models\Role;
use App\Models\System;
use App\Models\Ticket;
use App\Models\Trigger;
use App\Models\User;
use App\Platform\Actions\DetectInactiveDevelopersAction;
use App\Platform\Enums\AchievementAuthorTask;
use App\Support\Alerts\DeveloperInactivityAlert;
use App\Support\Alerts\Jobs\SendAlertWebhookJob;
use Database\Seeders\RolesTableSeeder;
use Illuminate\Database\Eloquent\Model;
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

    config(['services.discord.alerts_webhook.developer_inactivity' => 'https://discord.com/api/webhooks/test']);
});

afterEach(function (): void {
    Carbon::setTestNow();
});

function createDeveloperForInactivityTest(array $attributes = []): User
{
    /** @var User $user */
    $user = User::factory()->create(array_merge([
        'last_activity_at' => Carbon::now(),
    ], $attributes));

    $user->assignRole(Role::DEVELOPER);

    return $user;
}

function createGameForInactivityTest(): Game
{
    $system = System::factory()->create();

    /** @var Game $game */
    $game = Game::factory()->create(['system_id' => $system->id]);

    return $game;
}

function recordDeveloperAuditLog(User $developer, Model $subject, Carbon $at): void
{
    Activity::create([
        'log_name' => 'default',
        'description' => 'updated',
        'subject_type' => $subject->getMorphClass(),
        'subject_id' => $subject->getKey(),
        'causer_type' => $developer->getMorphClass(),
        'causer_id' => $developer->id,
        'created_at' => $at,
        'updated_at' => $at,
    ]);
}

it('queues the first inactivity finding for full developers', function (): void {
    // Arrange
    Carbon::setTestNow(Carbon::parse('2026-05-08 12:00:00'));

    $developer = createDeveloperForInactivityTest([
        'display_name' => 'InactiveDev',
        'last_activity_at' => Carbon::now()->subMonths(4),
    ]);

    // Act
    $findingsCount = (new DetectInactiveDevelopersAction())->execute();

    // Assert
    expect($findingsCount)->toBe(1);

    Queue::assertPushedOn('alerts', SendAlertWebhookJob::class, function (SendAlertWebhookJob $job) use ($developer): bool {
        return
            $job->alert instanceof DeveloperInactivityAlert
            && $job->alert->entries[0]['displayName'] === $developer->display_name
            && $job->alert->entries[0]['finding']['reason'] === 'overall_inactivity';
    });
});

it('ignores root and banned users who have a developer role', function (): void {
    // Arrange
    Carbon::setTestNow(Carbon::parse('2026-05-08 12:00:00'));

    $inactiveAttributes = ['last_activity_at' => Carbon::now()->subMonths(7)];

    $root = createDeveloperForInactivityTest($inactiveAttributes);
    $root->assignRole(Role::ROOT);

    createDeveloperForInactivityTest(array_merge($inactiveAttributes, [
        'banned_at' => Carbon::now()->subDay(),
    ]));

    // Act
    $findingsCount = (new DetectInactiveDevelopersAction())->execute();

    // Assert
    expect($findingsCount)->toBe(0);

    Queue::assertNotPushed(SendAlertWebhookJob::class);
});

it('uses durable developer activity sources to suppress developer inactivity findings', function (): void {
    // Arrange
    Carbon::setTestNow(Carbon::parse('2026-05-08 12:00:00'));

    $recentActivityAt = Carbon::now()->subMonth();
    $game = createGameForInactivityTest();

    $claimDeveloper = createDeveloperForInactivityTest();
    AchievementSetClaim::factory()->create([
        'user_id' => $claimDeveloper->id,
        'game_id' => $game->id,
        'created_at' => $recentActivityAt,
        'updated_at' => $recentActivityAt,
    ]);

    $achievementDeveloper = createDeveloperForInactivityTest();
    $authoredAchievement = Achievement::factory()->create([
        'game_id' => $game->id,
        'user_id' => $achievementDeveloper->id,
    ]);
    recordDeveloperAuditLog($achievementDeveloper, $authoredAchievement, $recentActivityAt);

    $codeNoteDeveloper = createDeveloperForInactivityTest();
    $memoryNote = MemoryNote::create([
        'user_id' => $codeNoteDeveloper->id,
        'game_id' => $game->id,
        'address' => 1,
        'body' => 'A useful code note',
    ]);
    recordDeveloperAuditLog($codeNoteDeveloper, $memoryNote, $recentActivityAt);

    $leaderboardDeveloper = createDeveloperForInactivityTest();
    $leaderboard = Leaderboard::factory()->create([
        'game_id' => $game->id,
        'author_id' => $leaderboardDeveloper->id,
    ]);
    recordDeveloperAuditLog($leaderboardDeveloper, $leaderboard, $recentActivityAt);

    $triggerDeveloper = createDeveloperForInactivityTest();
    $triggerAchievement = Achievement::factory()->create(['game_id' => $game->id]);
    $trigger = Trigger::factory()->create([
        'triggerable_id' => $triggerAchievement->id,
        'triggerable_type' => $triggerAchievement->getMorphClass(),
        'user_id' => $triggerDeveloper->id,
    ]);
    $trigger->forceFill(['updated_at' => $recentActivityAt])->saveQuietly();

    $ticketCommentDeveloper = createDeveloperForInactivityTest();
    Comment::create([
        'user_id' => $ticketCommentDeveloper->id,
        'commentable_type' => CommentableType::AchievementTicket,
        'commentable_id' => 123,
        'body' => 'I am looking into this ticket.',
        'created_at' => $recentActivityAt,
    ]);

    $ticketResolverDeveloper = createDeveloperForInactivityTest();
    $achievement = Achievement::factory()->create(['game_id' => $game->id]);
    Ticket::factory()->create([
        'ticketable_id' => $achievement->id,
        'resolver_id' => $ticketResolverDeveloper->id,
        'state' => TicketState::Resolved,
        'resolved_at' => $recentActivityAt,
    ]);

    // Act
    $findingsCount = (new DetectInactiveDevelopersAction())->execute();

    // Assert
    expect($findingsCount)->toBe(0);

    Queue::assertNotPushed(SendAlertWebhookJob::class);
});

it('does not count system-bumped claim updated_at as developer activity', function (): void {
    // Arrange
    Carbon::setTestNow(Carbon::parse('2026-05-08 12:00:00'));

    $developer = createDeveloperForInactivityTest();
    $game = createGameForInactivityTest();

    // mimic a stale claim that ProcessExpiringClaimsAction auto-extended via the system user.
    // created_at is old (the dev's original claim), but updated_at was just bumped by automation.
    AchievementSetClaim::factory()->create([
        'user_id' => $developer->id,
        'game_id' => $game->id,
        'created_at' => Carbon::now()->subYear(),
        'updated_at' => Carbon::now()->subDay(),
    ]);

    // Act
    $findingsCount = (new DetectInactiveDevelopersAction())->execute();

    // Assert
    expect($findingsCount)->toBe(1);

    Queue::assertPushed(SendAlertWebhookJob::class, function (SendAlertWebhookJob $job): bool {
        return
            $job->alert instanceof DeveloperInactivityAlert
            && $job->alert->entries[0]['finding']['reason'] === 'developer_inactivity';
    });
});

it('does not count audit log rows authored by other users as developer activity', function (): void {
    // Arrange
    Carbon::setTestNow(Carbon::parse('2026-05-08 12:00:00'));

    $developer = createDeveloperForInactivityTest();
    $otherUser = User::factory()->create();
    $achievement = Achievement::factory()->create([
        'game_id' => createGameForInactivityTest()->id,
        'user_id' => $developer->id,
        'created_at' => Carbon::now()->subYear(),
        'modified_at' => Carbon::now()->subDay(),
        'updated_at' => Carbon::now()->subDay(),
    ]);

    recordDeveloperAuditLog($otherUser, $achievement, Carbon::now()->subDay());

    // Act
    $findingsCount = (new DetectInactiveDevelopersAction())->execute();

    // Assert
    expect($findingsCount)->toBe(1);

    Queue::assertPushed(SendAlertWebhookJob::class, function (SendAlertWebhookJob $job): bool {
        return
            $job->alert instanceof DeveloperInactivityAlert
            && $job->alert->entries[0]['finding']['reason'] === 'developer_inactivity';
    });
});

it('does not treat achievement author rows as developer activity', function (): void {
    // Arrange
    Carbon::setTestNow(Carbon::parse('2026-05-08 12:00:00'));

    $developer = createDeveloperForInactivityTest();
    $achievement = Achievement::factory()->create([
        'game_id' => createGameForInactivityTest()->id,
        'user_id' => User::factory()->create()->id,
    ]);

    AchievementAuthor::create([
        'achievement_id' => $achievement->id,
        'user_id' => $developer->id,
        'task' => AchievementAuthorTask::Logic->value,
        'created_at' => Carbon::now()->subDay(),
        'updated_at' => Carbon::now()->subDay(),
    ]);

    // Act
    $findingsCount = (new DetectInactiveDevelopersAction())->execute();

    // Assert
    expect($findingsCount)->toBe(1);

    Queue::assertPushed(SendAlertWebhookJob::class, function (SendAlertWebhookJob $job): bool {
        return
            $job->alert instanceof DeveloperInactivityAlert
            && $job->alert->entries[0]['finding']['reason'] === 'developer_inactivity';
    });
});

it('dedupes findings by whatever is held in the cache', function (): void {
    // Arrange
    Carbon::setTestNow(Carbon::parse('2026-05-08 12:00:00'));

    $developer = createDeveloperForInactivityTest([
        'last_activity_at' => Carbon::now()->subMonths(4),
    ]);

    $firstRunFindingsCount = (new DetectInactiveDevelopersAction())->execute();
    $secondRunFindingsCount = (new DetectInactiveDevelopersAction())->execute();

    $developer->forceFill(['last_activity_at' => Carbon::now()->subMonths(5)])->save();

    // Act
    $thirdRunFindingsCount = (new DetectInactiveDevelopersAction())->execute();

    // Assert
    expect($firstRunFindingsCount)
        ->toBe(1)
        ->and($secondRunFindingsCount)->toBe(0)
        ->and($thirdRunFindingsCount)->toBe(1);
});

it('does not dispatch or dedupe findings when the webhook is not configured', function (): void {
    // Arrange
    Carbon::setTestNow(Carbon::parse('2026-05-08 12:00:00'));

    createDeveloperForInactivityTest([
        'last_activity_at' => Carbon::now()->subMonths(4),
    ]);

    config(['services.discord.alerts_webhook.developer_inactivity' => null]);

    $unconfiguredFindingsCount = (new DetectInactiveDevelopersAction())->execute();

    expect($unconfiguredFindingsCount)->toBe(0);
    Queue::assertNotPushed(SendAlertWebhookJob::class);

    config(['services.discord.alerts_webhook.developer_inactivity' => 'https://discord.com/api/webhooks/test']);

    // Act
    $configuredFindingsCount = (new DetectInactiveDevelopersAction())->execute();

    // Assert
    expect($configuredFindingsCount)->toBe(1);
});

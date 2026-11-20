<?php

declare(strict_types=1);

use App\Models\Achievement;
use App\Models\Game;
use App\Models\Leaderboard;
use App\Models\Ticket;
use App\Models\Trigger;
use App\Models\User;
use App\Platform\Actions\UpsertTriggerVersionAction;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function createTriggerForUpsertTest(Achievement $achievement, array $attributes): Trigger
{
    return Trigger::factory()->create(['triggerable_id' => $achievement->id, ...$attributes]);
}

function createDemotedAchievementForUpsertTest(Carbon $demotedAt): Achievement
{
    return Achievement::factory()->create([
        'is_promoted' => false,
        'demoted_at' => $demotedAt,
    ]);
}

it('given a model without the versioned trigger contract, returns null', function () {
    // ARRANGE
    $user = User::factory()->create();

    // ACT
    $trigger = (new UpsertTriggerVersionAction())->execute($user, 'test conditions');

    // ASSERT
    expect($trigger)->toBeNull();
});

it('given an achievement with no trigger, creates version 1', function () {
    // ARRANGE
    $achievement = Achievement::factory()->create();
    $user = User::factory()->create();

    // ACT
    $trigger = (new UpsertTriggerVersionAction())->execute($achievement, '0xHaaaa=0', versioned: true, user: $user);

    // ASSERT
    expect($trigger)->not->toBeNull();
    expect($trigger->conditions)->toEqual('0xHaaaa=0');
    expect($trigger->version)->toEqual(1);
    expect($trigger->user_id)->toEqual($user->id);
    expect($trigger->parent_id)->toBeNull();

    $achievement->refresh();
    expect($achievement->trigger->conditions)->toEqual($trigger->conditions);
});

it('given an unversioned trigger and an unversioned edit, updates the trigger in place', function () {
    // ARRANGE
    $achievement = Achievement::factory()->create();
    $user = User::factory()->create();

    $existingTrigger = createTriggerForUpsertTest($achievement, [
        'conditions' => '0xHaaaa=0',
        'version' => null, // !!
        'user_id' => User::factory()->create()->id,
    ]);

    // ACT
    $trigger = (new UpsertTriggerVersionAction())->execute($achievement, '0xHbbbb=0', versioned: false, user: $user);

    // ASSERT
    expect($trigger)->not->toBeNull();
    expect($trigger->id)->toEqual($existingTrigger->id);
    expect($trigger->conditions)->toEqual('0xHbbbb=0');
    expect($trigger->version)->toBeNull();
    expect($trigger->user_id)->toEqual($user->id);

    $achievement->refresh();
    expect($achievement->triggers()->unversioned()->first()->conditions)->toEqual($trigger->conditions);
});

it('given a versioned trigger and new conditions, creates the next version', function () {
    // ARRANGE
    $achievement = Achievement::factory()->create();
    $user = User::factory()->create();

    $existingTrigger = createTriggerForUpsertTest($achievement, [
        'conditions' => '0xHaaaa=0',
        'version' => 1, // !!
        'user_id' => User::factory()->create()->id,
    ]);

    // ACT
    $trigger = (new UpsertTriggerVersionAction())->execute($achievement, '0xHbbbb=0', versioned: true, user: $user);

    // ASSERT
    expect($trigger)->not->toBeNull();
    expect($trigger->id)->not->toEqual($existingTrigger->id);
    expect($trigger->conditions)->toEqual('0xHbbbb=0');
    expect($trigger->version)->toEqual(2); // !!
    expect($trigger->parent_id)->toEqual($existingTrigger->id);
    expect($trigger->user_id)->toEqual($user->id);

    $achievement->refresh();
    expect($achievement->trigger->conditions)->toEqual($trigger->conditions);
});

it('given unchanged conditions, returns the existing trigger', function () {
    // ARRANGE
    $achievement = Achievement::factory()->create();
    $user = User::factory()->create();

    $existingTrigger = createTriggerForUpsertTest($achievement, [
        'conditions' => '0xHaaaa=0', // !!
        'version' => 1,
        'user_id' => User::factory()->create()->id,
    ]);

    // ACT
    $trigger = (new UpsertTriggerVersionAction())->execute(
        $achievement,
        '0xHaaaa=0', // !!
        versioned: true,
        user: $user
    );

    // ASSERT
    expect($trigger)->not->toBeNull();
    expect($trigger->id)->toEqual($existingTrigger->id);
    expect($trigger->conditions)->toEqual('0xHaaaa=0');
    expect($trigger->version)->toEqual(1);
});

it('given an unversioned trigger being published, converts it to version 1 in place', function () {
    // ARRANGE
    $achievement = Achievement::factory()->create();
    $user = User::factory()->create();
    $author = User::factory()->create();

    $existingTrigger = createTriggerForUpsertTest($achievement, [
        'conditions' => '0xHaaaa=0',
        'version' => null, // !!
        'user_id' => $author->id,
    ]);

    // ACT
    $trigger = (new UpsertTriggerVersionAction())->execute(
        $achievement,
        '0xHaaaa=0', // !! we're not changing the condition
        versioned: true, // !! just setting the thing to versioned (publishing the achievement)
        user: $user
    );

    // ASSERT
    expect($trigger)->not->toBeNull();
    expect($trigger->id)->toEqual($existingTrigger->id); // !! the id didn't change
    expect($trigger->conditions)->toEqual('0xHaaaa=0');
    expect($trigger->version)->toEqual(1); // !! versioned now
    expect($trigger->user_id)->toEqual($author->id); // !! the author keeps the credit
});

it('given a demoted achievement with unversioned logic, keeps the old logic as version 1', function () {
    // ARRANGE
    $achievement = createDemotedAchievementForUpsertTest(now()->subYear());
    $user = User::factory()->create();

    $existingTrigger = createTriggerForUpsertTest($achievement, [
        'conditions' => '0xHaaaa=0',
        'version' => null, // !!
    ]);

    // ACT
    $trigger = (new UpsertTriggerVersionAction())->execute(
        $achievement,
        '0xHaaaa=1',
        versioned: true,
        user: $user
    );

    // ASSERT
    $existingTrigger->refresh();
    expect($existingTrigger->conditions)->toEqual('0xHaaaa=0');
    expect($existingTrigger->version)->toEqual(1);

    expect($trigger)->not->toBeNull();
    expect($trigger->conditions)->toEqual('0xHaaaa=1');
    expect($trigger->version)->toEqual(2);
    expect($trigger->parent_id)->toEqual($existingTrigger->id);
});

it('given a demoted achievement edited twice, replaces the unpublished version', function () {
    // ARRANGE
    $achievement = createDemotedAchievementForUpsertTest(now()->subYear());
    $firstEditor = User::factory()->create();
    $secondEditor = User::factory()->create();

    $publishedTrigger = createTriggerForUpsertTest($achievement, [
        'conditions' => '0xHaaaa=0',
        'version' => 1,
        'created_at' => now()->subYears(2), // !! published before the demotion
    ]);

    // ACT
    $firstEdit = (new UpsertTriggerVersionAction())->execute(
        $achievement->fresh(),
        '0xHaaaa=1',
        versioned: true,
        user: $firstEditor
    );
    $secondEdit = (new UpsertTriggerVersionAction())->execute(
        $achievement->fresh(),
        '0xHaaaa=2',
        versioned: true,
        user: $secondEditor
    );

    // ASSERT
    expect($secondEdit->id)->toEqual($firstEdit->id); // !! no third version
    expect($achievement->triggers()->count())->toEqual(2);

    $publishedTrigger->refresh();
    expect($publishedTrigger->conditions)->toEqual('0xHaaaa=0');
    expect($publishedTrigger->version)->toEqual(1);

    $secondEdit->refresh();
    expect($secondEdit->conditions)->toEqual('0xHaaaa=2');
    expect($secondEdit->version)->toEqual(2);
    expect($secondEdit->parent_id)->toEqual($publishedTrigger->id);
    expect($secondEdit->user_id)->toEqual($secondEditor->id);
    expect($achievement->fresh()->trigger_id)->toEqual($secondEdit->id);
});

it('given an unpublished version created in the same second as the demotion, replaces it', function () {
    // ARRANGE
    $demotedAt = now()->subYear()->startOfSecond();
    $achievement = createDemotedAchievementForUpsertTest($demotedAt);
    $user = User::factory()->create();

    $publishedTrigger = createTriggerForUpsertTest($achievement, [
        'conditions' => '0xHaaaa=0',
        'version' => 1,
        'created_at' => now()->subYears(2),
    ]);
    $unpublishedTrigger = createTriggerForUpsertTest($achievement, [
        'conditions' => '0xHaaaa=1',
        'version' => 2,
        'parent_id' => $publishedTrigger->id,
        'created_at' => $demotedAt, // !! saved in the same request as the demotion
    ]);

    // ACT
    $trigger = (new UpsertTriggerVersionAction())->execute(
        $achievement->fresh(),
        '0xHaaaa=2',
        versioned: true,
        user: $user
    );

    // ASSERT
    expect($trigger->id)->toEqual($unpublishedTrigger->id);
    expect($trigger->conditions)->toEqual('0xHaaaa=2');
    expect($achievement->triggers()->count())->toEqual(2);
});

it('given a version that was live before the demotion, keeps it', function () {
    // ARRANGE
    $achievement = createDemotedAchievementForUpsertTest(now()->subYear());
    $user = User::factory()->create();

    $firstTrigger = createTriggerForUpsertTest($achievement, [
        'conditions' => '0xHaaaa=0',
        'version' => 1,
        'created_at' => now()->subYears(2),
    ]);
    $liveTrigger = createTriggerForUpsertTest($achievement, [
        'conditions' => '0xHaaaa=1',
        'version' => 2,
        'parent_id' => $firstTrigger->id,
        'created_at' => now()->subMonths(18), // !! live while the achievement was promoted
    ]);

    // ACT
    $trigger = (new UpsertTriggerVersionAction())->execute(
        $achievement->fresh(),
        '0xHaaaa=2',
        versioned: true,
        user: $user
    );

    // ASSERT
    $liveTrigger->refresh();
    expect($liveTrigger->conditions)->toEqual('0xHaaaa=1');

    expect($trigger->version)->toEqual(3);
    expect($trigger->parent_id)->toEqual($liveTrigger->id);
});

it('given an unpublished version that a ticket points at, keeps it', function () {
    // ARRANGE
    $achievement = createDemotedAchievementForUpsertTest(now()->subYear());
    $user = User::factory()->create();

    $publishedTrigger = createTriggerForUpsertTest($achievement, [
        'conditions' => '0xHaaaa=0',
        'version' => 1,
        'created_at' => now()->subYears(2),
    ]);
    $ticketedTrigger = createTriggerForUpsertTest($achievement, [
        'conditions' => '0xHaaaa=1',
        'version' => 2,
        'parent_id' => $publishedTrigger->id,
        'created_at' => now()->subMonths(6),
    ]);
    Ticket::factory()->create([
        'ticketable_id' => $achievement->id,
        'reporter_id' => $user->id,
        'trigger_id' => $ticketedTrigger->id, // !!
    ]);

    // ACT
    $trigger = (new UpsertTriggerVersionAction())->execute(
        $achievement->fresh(),
        '0xHaaaa=2',
        versioned: true,
        user: $user
    );

    // ASSERT
    $ticketedTrigger->refresh();
    expect($ticketedTrigger->conditions)->toEqual('0xHaaaa=1');

    expect($trigger->version)->toEqual(3);
    expect($trigger->parent_id)->toEqual($ticketedTrigger->id);
});

it('given an achievement, a leaderboard, and a game, updates each denormalized trigger_id', function () {
    // ARRANGE
    $user = User::factory()->create();

    $achievement = Achievement::factory()->create(['trigger_id' => null]);
    $leaderboard = Leaderboard::factory()->create(['trigger_id' => null]);
    $game = Game::factory()->create(['trigger_id' => null]);

    // ACT
    $achievementTrigger = (new UpsertTriggerVersionAction())->execute($achievement, '0xH1234=1', user: $user);
    $leaderboardTrigger = (new UpsertTriggerVersionAction())->execute($leaderboard, '0xH5678=1', user: $user);
    $gameTrigger = (new UpsertTriggerVersionAction())->execute($game, '0xH1234=1', user: $user);

    // ASSERT
    $achievement->refresh();
    $leaderboard->refresh();
    $game->refresh();

    expect($achievement->trigger_id)->toEqual($achievementTrigger->id);
    expect($leaderboard->trigger_id)->toEqual($leaderboardTrigger->id);
    expect($game->trigger_id)->toEqual($gameTrigger->id);
});

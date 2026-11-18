<?php

declare(strict_types=1);

use App\Community\Actions\QueueAchievementSetReleaseNotificationsAction;
use App\Community\Enums\SubscriptionSubjectType;
use App\Models\Game;
use App\Models\System;
use App\Models\User;
use App\Models\UserDelayedSubscription;
use App\Models\UserGameListEntry;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function releaseNoticeCountForQueueTest(User $user, Game $game): int
{
    return UserDelayedSubscription::query()
        ->where('user_id', $user->id)
        ->where('subject_type', SubscriptionSubjectType::AchievementSetRelease)
        ->where('subject_id', $game->id)
        ->count();
}

function genesisGameForQueueTest(string $title = 'Ristar'): Game
{
    $system = System::factory()->create(['name' => 'Genesis/Mega Drive']);

    return Game::factory()->create(['title' => $title, 'system_id' => $system->id]);
}

it('given a want to play player with no set request, then it queues one notice', function () {
    // Arrange
    $game = genesisGameForQueueTest();
    $user = User::factory()->create();
    UserGameListEntry::factory()->play()->create(['user_id' => $user->id, 'game_id' => $game->id]);

    // Act
    (new QueueAchievementSetReleaseNotificationsAction())->execute($game);

    // Assert
    expect(releaseNoticeCountForQueueTest($user, $game))->toEqual(1);

    $notice = UserDelayedSubscription::where('user_id', $user->id)->sole();
    expect($notice->subject_id)->toEqual($game->id);
    expect($notice->first_update_id)->toEqual($game->id);
});

it('given a player who also requested the set, then it queues no notice for them', function () {
    // Arrange
    $game = genesisGameForQueueTest();

    $requester = User::factory()->create();
    UserGameListEntry::factory()->play()->create(['user_id' => $requester->id, 'game_id' => $game->id]);
    UserGameListEntry::factory()->setRequest()->create(['user_id' => $requester->id, 'game_id' => $game->id]);

    $waiter = User::factory()->create();
    UserGameListEntry::factory()->play()->create(['user_id' => $waiter->id, 'game_id' => $game->id]);

    // Act
    (new QueueAchievementSetReleaseNotificationsAction())->execute($game);

    // Assert
    expect(releaseNoticeCountForQueueTest($requester, $game))->toEqual(0);
    expect(releaseNoticeCountForQueueTest($waiter, $game))->toEqual(1);
});

it('given a banned player, then it queues no notice for them', function () {
    // Arrange
    $game = genesisGameForQueueTest();

    $banned = User::factory()->create(['banned_at' => now()->subDay()]);
    UserGameListEntry::factory()->play()->create(['user_id' => $banned->id, 'game_id' => $game->id]);

    $active = User::factory()->create();
    UserGameListEntry::factory()->play()->create(['user_id' => $active->id, 'game_id' => $game->id]);

    // Act
    (new QueueAchievementSetReleaseNotificationsAction())->execute($game);

    // Assert
    expect(releaseNoticeCountForQueueTest($banned, $game))->toEqual(0);
    expect(releaseNoticeCountForQueueTest($active, $game))->toEqual(1);
});

it('given entries for another game or another list kind, then it queues nothing', function () {
    // Arrange
    $game = genesisGameForQueueTest();
    $otherGame = genesisGameForQueueTest('Vectorman');

    $otherGameUser = User::factory()->create();
    UserGameListEntry::factory()->play()->create(['user_id' => $otherGameUser->id, 'game_id' => $otherGame->id]);

    $developer = User::factory()->create();
    UserGameListEntry::factory()->develop()->create(['user_id' => $developer->id, 'game_id' => $game->id]);

    // Act
    (new QueueAchievementSetReleaseNotificationsAction())->execute($game);

    // Assert
    expect(UserDelayedSubscription::count())->toEqual(0);
});

it('given several want to play players, then it queues a notice for each', function () {
    // Arrange
    $game = genesisGameForQueueTest();

    $first = User::factory()->create();
    $second = User::factory()->create();
    UserGameListEntry::factory()->play()->create(['user_id' => $first->id, 'game_id' => $game->id]);
    UserGameListEntry::factory()->play()->create(['user_id' => $second->id, 'game_id' => $game->id]);

    // Act
    (new QueueAchievementSetReleaseNotificationsAction())->execute($game);

    // Assert
    expect(releaseNoticeCountForQueueTest($first, $game))->toEqual(1);
    expect(releaseNoticeCountForQueueTest($second, $game))->toEqual(1);
});

it('given the action already ran for the game, then it queues no duplicate notice', function () {
    // Arrange
    $game = genesisGameForQueueTest();
    $user = User::factory()->create();
    UserGameListEntry::factory()->play()->create(['user_id' => $user->id, 'game_id' => $game->id]);

    // Act
    (new QueueAchievementSetReleaseNotificationsAction())->execute($game);
    (new QueueAchievementSetReleaseNotificationsAction())->execute($game);

    // Assert
    expect(releaseNoticeCountForQueueTest($user, $game))->toEqual(1);
});

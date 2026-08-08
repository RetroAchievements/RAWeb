<?php

declare(strict_types=1);

use App\Community\Actions\UpdateGameClaimAction;
use App\Community\Enums\ClaimSetType;
use App\Community\Enums\ClaimStatus;
use App\Community\Enums\SubscriptionSubjectType;
use App\Models\AchievementSetClaim;
use App\Models\Game;
use App\Models\System;
use App\Models\User;
use App\Models\UserDelayedSubscription;
use App\Models\UserGameListEntry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

function releaseNoticeCountForClaimTest(User $user, Game $game): int
{
    return UserDelayedSubscription::query()
        ->where('user_id', $user->id)
        ->where('subject_type', SubscriptionSubjectType::AchievementSetRelease)
        ->where('subject_id', $game->id)
        ->count();
}

it('given a want to play player and a new set claim, then it queues a release notice', function () {
    // Arrange
    Notification::fake();
    Http::fake();

    $admin = User::factory()->create();
    $this->actingAs($admin);

    $system = System::factory()->create(['name' => 'Genesis/Mega Drive']);
    $game = Game::factory()->create(['title' => 'Ristar', 'system_id' => $system->id]);

    $waiter = User::factory()->create();
    UserGameListEntry::factory()->play()->create(['user_id' => $waiter->id, 'game_id' => $game->id]);

    $claim = AchievementSetClaim::factory()->create([
        'game_id' => $game->id,
        'set_type' => ClaimSetType::NewSet,
        'status' => ClaimStatus::Active,
    ]);

    // Act
    (new UpdateGameClaimAction())->execute($claim, ['status' => ClaimStatus::Complete->value]);

    // Assert
    expect(releaseNoticeCountForClaimTest($waiter, $game))->toEqual(1);
});

it('given a want to play player and a revision claim, then it queues no release notice', function () {
    // Arrange
    Notification::fake();
    Http::fake();

    $admin = User::factory()->create();
    $this->actingAs($admin);

    $system = System::factory()->create(['name' => 'Genesis/Mega Drive']);
    $game = Game::factory()->create(['title' => 'Ristar', 'system_id' => $system->id]);

    $waiter = User::factory()->create();
    UserGameListEntry::factory()->play()->create(['user_id' => $waiter->id, 'game_id' => $game->id]);

    $claim = AchievementSetClaim::factory()->create([
        'game_id' => $game->id,
        'set_type' => ClaimSetType::Revision,
        'status' => ClaimStatus::Active,
    ]);

    // Act
    (new UpdateGameClaimAction())->execute($claim, ['status' => ClaimStatus::Complete->value]);

    // Assert
    expect(releaseNoticeCountForClaimTest($waiter, $game))->toEqual(0);
});

<?php

declare(strict_types=1);

use App\Community\Actions\ProcessDeveloperRoleChangeAction;
use App\Community\Enums\ClaimStatus;
use App\Community\Enums\ClaimType;
use App\Community\Enums\CommentableType;
use App\Enums\Permissions;
use App\Models\Achievement;
use App\Models\AchievementMaintainer;
use App\Models\AchievementSetClaim;
use App\Models\Comment;
use App\Models\Game;
use App\Models\Role;
use App\Models\System;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('given a developer loses their role, drops every open claim they hold', function () {
    // Arrange
    User::factory()->create(['id' => Comment::SYSTEM_USER_ID, 'username' => 'Server']);
    $actingModerator = User::factory()->create(['Permissions' => Permissions::Moderator, 'display_name' => 'Foo']);
    $developer = User::factory()->create(['Permissions' => Permissions::Registered, 'display_name' => 'Bar']);

    $console = System::factory()->create();
    $primaryClaimGame = Game::factory()->create(['system_id' => $console->id]);
    $collabClaimGame = Game::factory()->create(['system_id' => $console->id]);
    $finishedClaimGame = Game::factory()->create(['system_id' => $console->id]);

    $activePrimaryClaim = AchievementSetClaim::factory()->create([
        'user_id' => $developer->id,
        'game_id' => $primaryClaimGame->id,
        'claim_type' => ClaimType::Primary,
        'status' => ClaimStatus::Active,
    ]);
    $activeCollabClaim = AchievementSetClaim::factory()->create([
        'user_id' => $developer->id,
        'game_id' => $collabClaimGame->id,
        'claim_type' => ClaimType::Collaboration,
        'status' => ClaimStatus::Active,
    ]);
    $finishedClaim = AchievementSetClaim::factory()->create([
        'user_id' => $developer->id,
        'game_id' => $finishedClaimGame->id,
        'status' => ClaimStatus::Complete,
    ]);

    // Act
    (new ProcessDeveloperRoleChangeAction())->execute($developer, Role::DEVELOPER, null, $actingModerator);

    // Assert
    expect($activePrimaryClaim->fresh()->status)->toEqual(ClaimStatus::Dropped);
    expect($activeCollabClaim->fresh()->status)->toEqual(ClaimStatus::Dropped);
    expect($finishedClaim->fresh()->status)->toEqual(ClaimStatus::Complete);

    $collabClaimComment = Comment::query()
        ->where('commentable_type', CommentableType::SetClaim)
        ->where('commentable_id', $collabClaimGame->id)
        ->first();
    expect($collabClaimComment->body)->toEqual("Foo dropped Bar's Collaboration claim via demotion to Registered.");
});

it('given a junior developer loses their role, drops the claim that is still in review', function () {
    // Arrange
    User::factory()->create(['id' => Comment::SYSTEM_USER_ID, 'username' => 'Server']);
    $juniorDeveloper = User::factory()->create(['Permissions' => Permissions::Registered]);

    $claimedGame = Game::factory()->create(['system_id' => System::factory()->create()->id]);
    $pendingClaim = AchievementSetClaim::factory()->create([
        'user_id' => $juniorDeveloper->id,
        'game_id' => $claimedGame->id,
        'status' => ClaimStatus::InReview,
    ]);

    // Act
    (new ProcessDeveloperRoleChangeAction())->execute($juniorDeveloper, Role::DEVELOPER_JUNIOR, null);

    // Assert
    expect($pendingClaim->fresh()->status)->toEqual(ClaimStatus::Dropped);
});

it('given a junior developer is promoted to developer, activates the claims that are in review', function () {
    // Arrange
    User::factory()->create(['id' => Comment::SYSTEM_USER_ID, 'username' => 'Server']);
    $promotedDeveloper = User::factory()->create(['Permissions' => Permissions::Developer]);

    $claimedGame = Game::factory()->create(['system_id' => System::factory()->create()->id]);
    $pendingClaim = AchievementSetClaim::factory()->create([
        'user_id' => $promotedDeveloper->id,
        'game_id' => $claimedGame->id,
        'status' => ClaimStatus::InReview,
    ]);

    // Act
    (new ProcessDeveloperRoleChangeAction())->execute($promotedDeveloper, Role::DEVELOPER_JUNIOR, Role::DEVELOPER);

    // Assert
    expect($pendingClaim->fresh()->status)->toEqual(ClaimStatus::Active);
});

it('given a developer is demoted to junior developer, keeps their claims but expires their maintainerships', function () {
    // Arrange
    User::factory()->create(['id' => Comment::SYSTEM_USER_ID, 'username' => 'Server']);
    $demotedDeveloper = User::factory()->create(['Permissions' => Permissions::JuniorDeveloper]);

    $claimedGame = Game::factory()->create(['system_id' => System::factory()->create()->id]);
    $activeClaim = AchievementSetClaim::factory()->create([
        'user_id' => $demotedDeveloper->id,
        'game_id' => $claimedGame->id,
        'status' => ClaimStatus::Active,
    ]);

    $maintainedAchievement = Achievement::factory()->create(['game_id' => $claimedGame->id]);
    $activeMaintainership = AchievementMaintainer::create([
        'achievement_id' => $maintainedAchievement->id,
        'user_id' => $demotedDeveloper->id,
        'effective_from' => now()->subDays(9),
        'is_active' => true,
    ]);

    // Act
    (new ProcessDeveloperRoleChangeAction())->execute($demotedDeveloper, Role::DEVELOPER, Role::DEVELOPER_JUNIOR);

    // Assert
    expect($activeClaim->fresh()->status)->toEqual(ClaimStatus::Active);

    $activeMaintainership->refresh();
    expect($activeMaintainership->is_active)->toBeFalse();
    expect($activeMaintainership->effective_until)->not->toBeNull();
});

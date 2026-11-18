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
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

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

it('given a maintainer loses the developer role, reassigns their unresolved tickets back to the original author', function () {
    // Arrange
    User::factory()->create(['id' => Comment::SYSTEM_USER_ID, 'username' => 'Server']);
    $author = User::factory()->create();
    $demotedMaintainer = User::factory()->create(['Permissions' => Permissions::Registered]);
    $claimant = User::factory()->create();

    $game = Game::factory()->create(['system_id' => System::factory()->create()->id]);
    $achievement = Achievement::factory()->create(['game_id' => $game->id, 'user_id' => $author->id]);
    AchievementMaintainer::create([
        'achievement_id' => $achievement->id,
        'user_id' => $demotedMaintainer->id,
        'effective_from' => now()->subDays(30),
        'is_active' => true,
    ]);

    $openTicket = Ticket::factory()->forAchievement($achievement)->open()->create([
        'assignee_id' => $demotedMaintainer->id,
        'reporter_id' => User::factory()->create()->id,
    ]);
    $resolvedTicket = Ticket::factory()->forAchievement($achievement)->resolved()->create([
        'assignee_id' => $demotedMaintainer->id,
        'reporter_id' => User::factory()->create()->id,
    ]);
    $claimedTicket = Ticket::factory()->forAchievement($achievement)->open()->create([
        'assignee_id' => $claimant->id,
        'reporter_id' => User::factory()->create()->id,
    ]);

    // Act
    (new ProcessDeveloperRoleChangeAction())->execute($demotedMaintainer, Role::DEVELOPER, null);

    // Assert
    expect($openTicket->fresh()->assignee_id)->toEqual($author->id);
    expect($resolvedTicket->fresh()->assignee_id)->toEqual($demotedMaintainer->id);
    expect($claimedTicket->fresh()->assignee_id)->toEqual($claimant->id);
});

it('given a developer is demoted, reassigns every unresolved claimed ticket back to its maintainer', function () {
    // Arrange
    Notification::fake();
    User::factory()->create(['id' => Comment::SYSTEM_USER_ID, 'username' => 'Server']);
    $author = User::factory()->create();
    $demotedClaimant = User::factory()->create(['Permissions' => Permissions::Registered]);

    $game = Game::factory()->create(['system_id' => System::factory()->create()->id]);
    $authorsAchievement = Achievement::factory()->create(['game_id' => $game->id, 'user_id' => $author->id]);
    $claimantsOwnAchievement = Achievement::factory()->create(['game_id' => $game->id, 'user_id' => $demotedClaimant->id]);

    $openClaim = Ticket::factory()->forAchievement($authorsAchievement)->open()->create([
        'assignee_id' => $demotedClaimant->id,
        'reporter_id' => User::factory()->create()->id,
    ]);
    $requestClaim = Ticket::factory()->forAchievement($authorsAchievement)->request()->create([
        'assignee_id' => $demotedClaimant->id,
        'reporter_id' => User::factory()->create()->id,
    ]);
    $quarantinedClaim = Ticket::factory()->forAchievement($authorsAchievement)->quarantined()->create([
        'assignee_id' => $demotedClaimant->id,
        'reporter_id' => User::factory()->create()->id,
    ]);
    $ownTicket = Ticket::factory()->forAchievement($claimantsOwnAchievement)->request()->create([
        'assignee_id' => $demotedClaimant->id,
        'reporter_id' => User::factory()->create()->id,
    ]);

    // Act
    (new ProcessDeveloperRoleChangeAction())->execute($demotedClaimant, Role::DEVELOPER, null);

    // Assert
    expect($openClaim->fresh()->assignee_id)->toEqual($author->id);
    expect($requestClaim->fresh()->assignee_id)->toEqual($author->id);
    expect($requestClaim->auditLog()->where('event', Ticket::CLAIM_EXPIRED_EVENT)->count())->toEqual(1);
    expect($quarantinedClaim->fresh()->assignee_id)->toEqual($author->id);
    expect($ownTicket->fresh()->assignee_id)->toEqual($demotedClaimant->id);
    expect($ownTicket->auditLog()->where('event', Ticket::CLAIM_EXPIRED_EVENT)->exists())->toBeFalse();
});

it('given a developer who holds a ticket claim is demoted to junior developer, keeps the claim', function () {
    // Arrange
    User::factory()->create(['id' => Comment::SYSTEM_USER_ID, 'username' => 'Server']);
    $author = User::factory()->create();
    $demotedDeveloper = User::factory()->create(['Permissions' => Permissions::JuniorDeveloper]);

    $game = Game::factory()->create(['system_id' => System::factory()->create()->id]);
    $achievement = Achievement::factory()->create(['game_id' => $game->id, 'user_id' => $author->id]);
    $claimedTicket = Ticket::factory()->forAchievement($achievement)->request()->create([
        'assignee_id' => $demotedDeveloper->id,
        'reporter_id' => User::factory()->create()->id,
    ]);

    // Act
    (new ProcessDeveloperRoleChangeAction())->execute($demotedDeveloper, Role::DEVELOPER, Role::DEVELOPER_JUNIOR);

    // Assert
    expect($claimedTicket->fresh()->assignee_id)->toEqual($demotedDeveloper->id);
});

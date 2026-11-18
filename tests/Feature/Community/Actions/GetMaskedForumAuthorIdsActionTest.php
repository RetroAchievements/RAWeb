<?php

declare(strict_types=1);

use App\Community\Actions\GetMaskedForumAuthorIdsAction;
use App\Community\Enums\UserRelationStatus;
use App\Models\User;
use App\Models\UserRelation;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('given no viewer, it returns an empty set', function () {
    // ACT
    $result = (new GetMaskedForumAuthorIdsAction())->execute(null);

    // ASSERT
    expect($result)->toEqual([]);
});

it('given a viewer who blocked nobody, it returns an empty set', function () {
    // ARRANGE
    $viewer = User::factory()->create();

    // ACT
    $result = (new GetMaskedForumAuthorIdsAction())->execute($viewer);

    // ASSERT
    expect($result)->toEqual([]);
});

it('given a viewer who blocked two users, then it returns exactly those ids', function () {
    // ARRANGE
    $viewer = User::factory()->create();
    $blockedOne = User::factory()->create();
    $blockedTwo = User::factory()->create();

    UserRelation::factory()->blocked()->create([
        'user_id' => $viewer->id,
        'related_user_id' => $blockedOne->id,
    ]);
    UserRelation::factory()->blocked()->create([
        'user_id' => $viewer->id,
        'related_user_id' => $blockedTwo->id,
    ]);

    // ACT
    $result = (new GetMaskedForumAuthorIdsAction())->execute($viewer);

    // ASSERT
    expect($result)->toHaveCount(2);
    expect($result)->toContain($blockedOne->id);
    expect($result)->toContain($blockedTwo->id);
});

it('given a viewer who only follows another user, that user is not masked', function () {
    // ARRANGE
    $viewer = User::factory()->create();
    $followed = User::factory()->create();

    UserRelation::factory()->following()->create([
        'user_id' => $viewer->id,
        'related_user_id' => $followed->id,
    ]);

    // ACT
    $result = (new GetMaskedForumAuthorIdsAction())->execute($viewer);

    // ASSERT
    expect($result)->toEqual([]);
});

it('given a blocked user who follows the viewer back, the block still masks them', function () {
    // ARRANGE
    $viewer = User::factory()->create();
    $blocked = User::factory()->create();

    UserRelation::factory()->blocked()->create([
        'user_id' => $viewer->id,
        'related_user_id' => $blocked->id,
    ]);
    UserRelation::factory()->following()->create([
        'user_id' => $blocked->id,
        'related_user_id' => $viewer->id,
    ]);

    // ACT
    $result = (new GetMaskedForumAuthorIdsAction())->execute($viewer);

    // ASSERT
    expect($result)->toEqual([$blocked->id]);
});

it('given the viewer was blocked by someone but blocked nobody, it returns an empty set', function () {
    // ARRANGE
    $viewer = User::factory()->create();
    $blocker = User::factory()->create();

    UserRelation::factory()->blocked()->create([
        'user_id' => $blocker->id,
        'related_user_id' => $viewer->id,
    ]);

    // ACT
    $result = (new GetMaskedForumAuthorIdsAction())->execute($viewer);

    // ASSERT
    expect($result)->toEqual([]);
});

it('given a blocked user is a team account, that account is not masked', function () {
    // ARRANGE
    $viewer = User::factory()->create();
    $teamAccount = User::factory()->create(['username' => 'RAdmin']);
    $regularUser = User::factory()->create();

    UserRelation::factory()->blocked()->create([
        'user_id' => $viewer->id,
        'related_user_id' => $teamAccount->id,
    ]);
    UserRelation::factory()->blocked()->create([
        'user_id' => $viewer->id,
        'related_user_id' => $regularUser->id,
    ]);

    // ACT
    $result = (new GetMaskedForumAuthorIdsAction())->execute($viewer);

    // ASSERT
    expect($result)->toEqual([$regularUser->id]);
});

it('invalidates the cached set when a relation becomes blocked', function () {
    // ARRANGE
    $viewer = User::factory()->create();
    $blocked = User::factory()->create();
    $relation = UserRelation::factory()->following()->create([
        'user_id' => $viewer->id,
        'related_user_id' => $blocked->id,
    ]);
    $action = new GetMaskedForumAuthorIdsAction();
    expect($action->execute($viewer))->toEqual([]);

    // ACT
    $relation->status = UserRelationStatus::Blocked;
    $relation->save();

    // ASSERT
    expect($action->execute($viewer))->toEqual([$blocked->id]);
});

it('invalidates the cached set when a blocked relation is deleted', function () {
    // ARRANGE
    $viewer = User::factory()->create();
    $blocked = User::factory()->create();
    $relation = UserRelation::factory()->blocked()->create([
        'user_id' => $viewer->id,
        'related_user_id' => $blocked->id,
    ]);
    $action = new GetMaskedForumAuthorIdsAction();
    expect($action->execute($viewer))->toEqual([$blocked->id]);

    // ACT
    $relation->delete();

    // ASSERT
    expect($action->execute($viewer))->toEqual([]);
});

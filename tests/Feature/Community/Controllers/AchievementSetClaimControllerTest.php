<?php

declare(strict_types=1);

namespace Tests\Feature\Community\Controllers;

use App\Community\Enums\ClaimSetType;
use App\Community\Enums\ClaimSpecial;
use App\Community\Enums\ClaimStatus;
use App\Community\Enums\ClaimType;
use App\Community\Enums\SubscriptionSubjectType;
use App\Models\Achievement;
use App\Models\ForumTopicComment;
use App\Models\Game;
use App\Models\Role;
use App\Models\Subscription;
use App\Models\User;
use App\Platform\Enums\AchievementFlag;
use Database\Seeders\RolesTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Session;
use Tests\TestCase;

class AchievementSetClaimControllerTest extends TestCase
{
    use RefreshDatabase;

    public function testPrimaryClaimAndComplete(): void
    {
        $this->seed(RolesTableSeeder::class);

        /** @var User $user */
        $user = User::factory()->create();
        $user->assignRole(Role::DEVELOPER);

        /** @var Game $game */
        $game = $this->seedGame(withHash: false);

        // initial claim
        $claimDate = Carbon::now()->startOfSecond();
        Carbon::setTestNow($claimDate);

        $response = $this->actingAs($user)->postJson(route('achievement-set-claim.create', $game->id));

        $response->assertStatus(302); // redirect
        $response->assertRedirect('/'); // back() redirects to home when no source is provided
        $response->assertSessionHas('success', 'Claim created successfully');

        $claim = $game->achievementSetClaims()->first();
        $this->assertNotNull($claim);
        $this->assertEquals($user->id, $claim->user_id);
        $this->assertEquals($game->id, $claim->game_id);
        $this->assertEquals(ClaimType::Primary, $claim->ClaimType);
        $this->assertEquals(ClaimSetType::NewSet, $claim->SetType);
        $this->assertEquals(ClaimStatus::Active, $claim->Status);
        $this->assertEquals(ClaimSpecial::None, $claim->Special);
        $this->assertEquals($claimDate->clone()->addMonths(3), $claim->Finished);
        $this->assertEquals($claimDate, $claim->Created);
        $this->assertEquals($claimDate, $claim->Updated);
        $this->assertEquals(0, $claim->Extension);

        // user should automatically be subscribed to the game wall
        $this->assertTrue($user->subscriptions()
            ->where('subject_type', SubscriptionSubjectType::GameWall)
            ->where('subject_id', $game->id)
            ->where('state', true)
            ->exists()
        );

        // forum topic should automatically be created by the user
        $game->refresh();
        $this->assertGreaterThan(0, $game->ForumTopicID);

        $this->assertTrue(ForumTopicComment::where('ForumTopicID', $game->ForumTopicID)
            ->where('author_id', $user->ID)
            ->exists()
        );

        // implicit subscription to forum topic. don't need an explicit one
        $this->assertFalse($user->subscriptions()
            ->where('subject_type', SubscriptionSubjectType::ForumTopic)
            ->where('subject_id', $game->ForumTopicID)
            ->exists()
        );

        // complete claim
        $completeDate = $claimDate->clone()->addDays(41);
        Carbon::setTestNow($completeDate);

        $response = $this->actingAs($user)->postJson(route('achievement-set-claim.update', $claim->ID), [
            'status' => ClaimStatus::Complete,
        ]);

        $response->assertStatus(302); // redirect
        $response->assertRedirect('/'); // back() redirects to home when no source is provided
        $response->assertSessionHas('success', 'Claim updated successfully');

        $claim->refresh();
        $this->assertEquals($user->id, $claim->user_id);
        $this->assertEquals($game->id, $claim->game_id);
        $this->assertEquals(ClaimType::Primary, $claim->ClaimType);
        $this->assertEquals(ClaimSetType::NewSet, $claim->SetType);
        $this->assertEquals(ClaimStatus::Complete, $claim->Status);
        $this->assertEquals(ClaimSpecial::None, $claim->Special);
        $this->assertEquals($completeDate, $claim->Finished);
        $this->assertEquals($claimDate, $claim->Created);
        $this->assertEquals($completeDate, $claim->Updated);
        $this->assertEquals(0, $claim->Extension);

        // attempt to drop claim
        $dropDate = $completeDate->clone()->addDays(23);
        Carbon::setTestNow($dropDate);

        $response = $this->actingAs($user)->postJson(route('achievement-set-claim.delete', $game->id));

        $response->assertStatus(302); // redirect
        $response->assertRedirect('/'); // back() redirects to home when no source is provided
        $response->assertSessionHas('error', 'You do not have a claim on this game.');

        $claim->refresh();
        $this->assertEquals($user->id, $claim->user_id);
        $this->assertEquals($game->id, $claim->game_id);
        $this->assertEquals(ClaimType::Primary, $claim->ClaimType);
        $this->assertEquals(ClaimSetType::NewSet, $claim->SetType);
        $this->assertEquals(ClaimStatus::Complete, $claim->Status);
        $this->assertEquals(ClaimSpecial::None, $claim->Special);
        $this->assertEquals($completeDate, $claim->Finished);
        $this->assertEquals($claimDate, $claim->Created);
        $this->assertEquals($completeDate, $claim->Updated);
        $this->assertEquals(0, $claim->Extension);
    }

    public function testExtendAndDropPrimaryClaim(): void
    {
        $this->seed(RolesTableSeeder::class);

        /** @var User $user */
        $user = User::factory()->create();
        $user->assignRole(Role::DEVELOPER);

        /** @var Game $game */
        $game = $this->seedGame(withHash: false);

        // initial claim
        $claimDate = Carbon::now()->startOfSecond();
        Carbon::setTestNow($claimDate);

        $response = $this->actingAs($user)->postJson(route('achievement-set-claim.create', $game->id));

        $response->assertStatus(302); // redirect
        $response->assertRedirect('/'); // back() redirects to home when no source is provided
        $response->assertSessionHas('success', 'Claim created successfully');

        $claim = $game->achievementSetClaims()->first();
        $this->assertNotNull($claim);
        $this->assertEquals($user->id, $claim->user_id);
        $this->assertEquals($game->id, $claim->game_id);
        $this->assertEquals(ClaimType::Primary, $claim->ClaimType);
        $this->assertEquals(ClaimSetType::NewSet, $claim->SetType);
        $this->assertEquals(ClaimStatus::Active, $claim->Status);
        $this->assertEquals(ClaimSpecial::None, $claim->Special);
        $this->assertEquals($claimDate->clone()->addMonths(3), $claim->Finished);
        $this->assertEquals($claimDate, $claim->Created);
        $this->assertEquals($claimDate, $claim->Updated);
        $this->assertEquals(0, $claim->Extension);

        // extend claim
        $claimFinished = $claim->Finished;
        $extendDate = $claimDate->clone()->addWeeks(11);
        Carbon::setTestNow($extendDate);

        $response = $this->actingAs($user)->postJson(route('achievement-set-claim.create', $game->id));

        $response->assertStatus(302); // redirect
        $response->assertRedirect('/'); // back() redirects to home when no source is provided
        $response->assertSessionHas('success', 'Claim updated successfully');

        $claim->refresh();
        $this->assertEquals($user->id, $claim->user_id);
        $this->assertEquals($game->id, $claim->game_id);
        $this->assertEquals(ClaimType::Primary, $claim->ClaimType);
        $this->assertEquals(ClaimSetType::NewSet, $claim->SetType);
        $this->assertEquals(ClaimStatus::Active, $claim->Status);
        $this->assertEquals(ClaimSpecial::None, $claim->Special);
        $this->assertEquals($claimFinished->clone()->addMonths(3), $claim->Finished);
        $this->assertEquals($claimDate, $claim->Created);
        $this->assertEquals($extendDate, $claim->Updated);
        $this->assertEquals(1, $claim->Extension);

        // drop claim
        $dropDate = $extendDate->clone()->addDays(23);
        Carbon::setTestNow($dropDate);

        $response = $this->actingAs($user)->postJson(route('achievement-set-claim.delete', $game->id));

        $response->assertStatus(302); // redirect
        $response->assertRedirect('/'); // back() redirects to home when no source is provided
        $response->assertSessionHas('success', 'Claim dropped successfully');

        $claim->refresh();
        $this->assertEquals($user->id, $claim->user_id);
        $this->assertEquals($game->id, $claim->game_id);
        $this->assertEquals(ClaimType::Primary, $claim->ClaimType);
        $this->assertEquals(ClaimSetType::NewSet, $claim->SetType);
        $this->assertEquals(ClaimStatus::Dropped, $claim->Status);
        $this->assertEquals(ClaimSpecial::None, $claim->Special);
        $this->assertEquals($dropDate, $claim->Finished);
        $this->assertEquals($claimDate, $claim->Created);
        $this->assertEquals($dropDate, $claim->Updated);
        $this->assertEquals(1, $claim->Extension);

        // attempt to drop claim again
        $dropAgainDate = $extendDate->clone()->addMinutes(2);
        Carbon::setTestNow($dropAgainDate);

        $response = $this->actingAs($user)->postJson(route('achievement-set-claim.delete', $game->id));

        $response->assertStatus(302); // redirect
        $response->assertRedirect('/'); // back() redirects to home when no source is provided
        $response->assertSessionHas('error', 'You do not have a claim on this game.');

        $claim->refresh();
        $this->assertEquals($user->id, $claim->user_id);
        $this->assertEquals($game->id, $claim->game_id);
        $this->assertEquals(ClaimType::Primary, $claim->ClaimType);
        $this->assertEquals(ClaimSetType::NewSet, $claim->SetType);
        $this->assertEquals(ClaimStatus::Dropped, $claim->Status);
        $this->assertEquals(ClaimSpecial::None, $claim->Special);
        $this->assertEquals($dropDate, $claim->Finished);
        $this->assertEquals($claimDate, $claim->Created);
        $this->assertEquals($dropDate, $claim->Updated);
        $this->assertEquals(1, $claim->Extension);

        // re-claim
        $reclaimDate = Carbon::now()->startOfSecond();
        Carbon::setTestNow($reclaimDate);

        $response = $this->actingAs($user)->postJson(route('achievement-set-claim.create', $game->id));

        $response->assertStatus(302); // redirect
        $response->assertRedirect('/'); // back() redirects to home when no source is provided
        $response->assertSessionHas('success', 'Claim created successfully');

        $newClaim = $game->achievementSetClaims()->active()->first();
        $this->assertNotNull($newClaim);
        $this->assertNotEquals($newClaim->ID, $claim->ID);
        $this->assertEquals($user->id, $newClaim->user_id);
        $this->assertEquals($game->id, $newClaim->game_id);
        $this->assertEquals(ClaimType::Primary, $newClaim->ClaimType);
        $this->assertEquals(ClaimSetType::NewSet, $newClaim->SetType);
        $this->assertEquals(ClaimStatus::Active, $newClaim->Status);
        $this->assertEquals(ClaimSpecial::None, $newClaim->Special);
        $this->assertEquals($reclaimDate->clone()->addMonths(3), $newClaim->Finished);
        $this->assertEquals($reclaimDate, $newClaim->Created);
        $this->assertEquals($reclaimDate, $newClaim->Updated);
        $this->assertEquals(0, $newClaim->Extension);
    }

    public function testCollaborationClaimAndDrop(): void
    {
        $this->seed(RolesTableSeeder::class);

        /** @var User $user */
        $user = User::factory()->create();
        $user->assignRole(Role::DEVELOPER);

        /** @var User $user2 */
        $user2 = User::factory()->create();
        $user2->assignRole(Role::DEVELOPER);

        /** @var Game $game */
        $game = $this->seedGame(withHash: false);

        // initial claim
        $claimDate = Carbon::now()->startOfSecond();
        Carbon::setTestNow($claimDate);

        $response = $this->actingAs($user)->postJson(route('achievement-set-claim.create', $game->id));

        $response->assertStatus(302); // redirect
        $response->assertRedirect('/'); // back() redirects to home when no source is provided
        $response->assertSessionHas('success', 'Claim created successfully');

        $claim = $game->achievementSetClaims()->first();
        $this->assertNotNull($claim);

        // collaboration claim
        $collabDate = $claimDate->clone()->addHours(2);
        Carbon::setTestNow($collabDate);

        Session::flush();
        $response = $this->actingAs($user2)->postJson(route('achievement-set-claim.create', $game->ID));

        $response->assertStatus(302); // redirect
        $response->assertRedirect('/'); // back() redirects to home when no source is provided
        $response->assertSessionHas('success', 'Claim created successfully');

        $collabClaim = $game->achievementSetClaims()->where('user_id', $user2->id)->first();
        $this->assertNotNull($collabClaim);
        $this->assertEquals($user2->id, $collabClaim->user_id);
        $this->assertEquals($game->id, $collabClaim->game_id);
        $this->assertEquals(ClaimType::Collaboration, $collabClaim->ClaimType);
        $this->assertEquals(ClaimSetType::NewSet, $collabClaim->SetType);
        $this->assertEquals(ClaimStatus::Active, $collabClaim->Status);
        $this->assertEquals(ClaimSpecial::None, $collabClaim->Special);
        $this->assertEquals($collabDate->clone()->addMonths(3), $collabClaim->Finished);
        $this->assertEquals($collabDate, $collabClaim->Created);
        $this->assertEquals($collabDate, $collabClaim->Updated);
        $this->assertEquals(0, $collabClaim->Extension);

        // drop claim
        $dropDate = $collabDate->clone()->addDays(23);
        Carbon::setTestNow($dropDate);

        $response = $this->actingAs($user2)->postJson(route('achievement-set-claim.delete', $game->id));

        $response->assertStatus(302); // redirect
        $response->assertRedirect('/'); // back() redirects to home when no source is provided
        $response->assertSessionHas('success', 'Claim dropped successfully');

        $collabClaim->refresh();
        $this->assertEquals($user2->id, $collabClaim->user_id);
        $this->assertEquals($game->id, $collabClaim->game_id);
        $this->assertEquals(ClaimType::Collaboration, $collabClaim->ClaimType);
        $this->assertEquals(ClaimSetType::NewSet, $collabClaim->SetType);
        $this->assertEquals(ClaimStatus::Dropped, $collabClaim->Status);
        $this->assertEquals(ClaimSpecial::None, $collabClaim->Special);
        $this->assertEquals($dropDate, $collabClaim->Finished);
        $this->assertEquals($collabDate, $collabClaim->Created);
        $this->assertEquals($dropDate, $collabClaim->Updated);
        $this->assertEquals(0, $collabClaim->Extension);

        // primary claim is unaffected
        $claim = $game->achievementSetClaims()->where('user_id', $user->id)->first();
        $this->assertNotNull($claim);
        $this->assertEquals($user->id, $claim->user_id);
        $this->assertEquals($game->id, $claim->game_id);
        $this->assertEquals(ClaimType::Primary, $claim->ClaimType);
        $this->assertEquals(ClaimSetType::NewSet, $claim->SetType);
        $this->assertEquals(ClaimStatus::Active, $claim->Status);
        $this->assertEquals(ClaimSpecial::None, $claim->Special);
        $this->assertEquals($claimDate->clone()->addMonths(3), $claim->Finished);
        $this->assertEquals($claimDate, $claim->Created);
        $this->assertEquals($claimDate, $claim->Updated);
        $this->assertEquals(0, $claim->Extension);
    }

    public function testCollaborationClaimAndPrimaryDrop(): void
    {
        $this->seed(RolesTableSeeder::class);

        /** @var User $user */
        $user = User::factory()->create();
        $user->assignRole(Role::DEVELOPER);

        /** @var User $user2 */
        $user2 = User::factory()->create();
        $user2->assignRole(Role::DEVELOPER);

        /** @var Game $game */
        $game = $this->seedGame(withHash: false);

        // initial claim
        $claimDate = Carbon::now()->startOfSecond();
        Carbon::setTestNow($claimDate);

        $response = $this->actingAs($user)->postJson(route('achievement-set-claim.create', $game->id));

        $response->assertStatus(302); // redirect
        $response->assertRedirect('/'); // back() redirects to home when no source is provided
        $response->assertSessionHas('success', 'Claim created successfully');

        $claim = $game->achievementSetClaims()->first();
        $this->assertNotNull($claim);

        // collaboration claim
        $collabDate = $claimDate->clone()->addHours(2);
        Carbon::setTestNow($collabDate);

        Session::flush();
        $response = $this->actingAs($user2)->postJson(route('achievement-set-claim.create', $game->ID));

        $response->assertStatus(302); // redirect
        $response->assertRedirect('/'); // back() redirects to home when no source is provided
        $response->assertSessionHas('success', 'Claim created successfully');

        $collabClaim = $game->achievementSetClaims()->where('user_id', $user2->id)->first();
        $this->assertNotNull($collabClaim);
        $this->assertEquals($user2->id, $collabClaim->user_id);
        $this->assertEquals($game->id, $collabClaim->game_id);
        $this->assertEquals(ClaimType::Collaboration, $collabClaim->ClaimType);
        $this->assertEquals(ClaimSetType::NewSet, $collabClaim->SetType);
        $this->assertEquals(ClaimStatus::Active, $collabClaim->Status);
        $this->assertEquals(ClaimSpecial::None, $collabClaim->Special);
        $this->assertEquals($collabDate->clone()->addMonths(3), $collabClaim->Finished);
        $this->assertEquals($collabDate, $collabClaim->Created);
        $this->assertEquals($collabDate, $collabClaim->Updated);
        $this->assertEquals(0, $collabClaim->Extension);

        // drop primary claim
        $dropDate = $collabDate->clone()->addDays(23);
        Carbon::setTestNow($dropDate);

        Session::flush();
        $response = $this->actingAs($user)->postJson(route('achievement-set-claim.delete', $game->id));

        $response->assertStatus(302); // redirect
        $response->assertRedirect('/'); // back() redirects to home when no source is provided
        $response->assertSessionHas('success', 'Claim dropped successfully');

        $claim->refresh();
        $this->assertEquals($user->id, $claim->user_id);
        $this->assertEquals($game->id, $claim->game_id);
        $this->assertEquals(ClaimType::Primary, $claim->ClaimType);
        $this->assertEquals(ClaimSetType::NewSet, $claim->SetType);
        $this->assertEquals(ClaimStatus::Dropped, $claim->Status);
        $this->assertEquals(ClaimSpecial::None, $claim->Special);
        $this->assertEquals($dropDate, $claim->Finished);
        $this->assertEquals($claimDate, $claim->Created);
        $this->assertEquals($dropDate, $claim->Updated);
        $this->assertEquals(0, $claim->Extension);

        // collab claim should be promoted to primary claim
        $collabClaim->refresh();
        $this->assertEquals($user2->id, $collabClaim->user_id);
        $this->assertEquals($game->id, $collabClaim->game_id);
        $this->assertEquals(ClaimType::Primary, $collabClaim->ClaimType);
        $this->assertEquals(ClaimSetType::NewSet, $collabClaim->SetType);
        $this->assertEquals(ClaimStatus::Active, $collabClaim->Status);
        $this->assertEquals(ClaimSpecial::None, $collabClaim->Special);
        $this->assertEquals($collabDate->clone()->addMonths(3), $collabClaim->Finished);
        $this->assertEquals($collabDate, $collabClaim->Created);
        $this->assertEquals($dropDate, $collabClaim->Updated);
        $this->assertEquals(0, $collabClaim->Extension);
    }

    public function testCollaborationClaimAndPrimaryComplete(): void
    {
        $this->seed(RolesTableSeeder::class);

        /** @var User $user */
        $user = User::factory()->create();
        $user->assignRole(Role::DEVELOPER);

        /** @var User $user2 */
        $user2 = User::factory()->create();
        $user2->assignRole(Role::DEVELOPER);

        /** @var Game $game */
        $game = $this->seedGame(withHash: false);

        // initial claim
        $claimDate = Carbon::now()->startOfSecond();
        Carbon::setTestNow($claimDate);

        $response = $this->actingAs($user)->postJson(route('achievement-set-claim.create', $game->id));

        $response->assertStatus(302); // redirect
        $response->assertRedirect('/'); // back() redirects to home when no source is provided
        $response->assertSessionHas('success', 'Claim created successfully');

        $claim = $game->achievementSetClaims()->first();
        $this->assertNotNull($claim);

        // collaboration claim
        $collabDate = $claimDate->clone()->addHours(2);
        Carbon::setTestNow($collabDate);

        Session::flush();
        $response = $this->actingAs($user2)->postJson(route('achievement-set-claim.create', $game->ID));

        $response->assertStatus(302); // redirect
        $response->assertRedirect('/'); // back() redirects to home when no source is provided
        $response->assertSessionHas('success', 'Claim created successfully');

        $collabClaim = $game->achievementSetClaims()->where('user_id', $user2->id)->first();
        $this->assertNotNull($collabClaim);
        $this->assertEquals($user2->id, $collabClaim->user_id);
        $this->assertEquals($game->id, $collabClaim->game_id);
        $this->assertEquals(ClaimType::Collaboration, $collabClaim->ClaimType);
        $this->assertEquals(ClaimSetType::NewSet, $collabClaim->SetType);
        $this->assertEquals(ClaimStatus::Active, $collabClaim->Status);
        $this->assertEquals(ClaimSpecial::None, $collabClaim->Special);
        $this->assertEquals($collabDate->clone()->addMonths(3), $collabClaim->Finished);
        $this->assertEquals($collabDate, $collabClaim->Created);
        $this->assertEquals($collabDate, $collabClaim->Updated);
        $this->assertEquals(0, $collabClaim->Extension);

        // complete primary claim
        $completeDate = $collabDate->clone()->addDays(23);
        Carbon::setTestNow($completeDate);

        Session::flush();
        $response = $this->actingAs($user)->postJson(route('achievement-set-claim.update', $game->id), ['status' => ClaimStatus::Complete]);

        $response->assertStatus(302); // redirect
        $response->assertRedirect('/'); // back() redirects to home when no source is provided
        $response->assertSessionHas('success', 'Claim updated successfully');

        $claim->refresh();
        $this->assertEquals($user->id, $claim->user_id);
        $this->assertEquals($game->id, $claim->game_id);
        $this->assertEquals(ClaimType::Primary, $claim->ClaimType);
        $this->assertEquals(ClaimSetType::NewSet, $claim->SetType);
        $this->assertEquals(ClaimStatus::Complete, $claim->Status);
        $this->assertEquals(ClaimSpecial::None, $claim->Special);
        $this->assertEquals($completeDate, $claim->Finished);
        $this->assertEquals($claimDate, $claim->Created);
        $this->assertEquals($completeDate, $claim->Updated);
        $this->assertEquals(0, $claim->Extension);

        // collab claim should be also be completed
        $collabClaim->refresh();
        $this->assertEquals($user2->id, $collabClaim->user_id);
        $this->assertEquals($game->id, $collabClaim->game_id);
        $this->assertEquals(ClaimType::Collaboration, $collabClaim->ClaimType);
        $this->assertEquals(ClaimSetType::NewSet, $collabClaim->SetType);
        $this->assertEquals(ClaimStatus::Complete, $collabClaim->Status);
        $this->assertEquals(ClaimSpecial::None, $collabClaim->Special);
        $this->assertEquals($completeDate, $collabClaim->Finished);
        $this->assertEquals($collabDate, $collabClaim->Created);
        $this->assertEquals($completeDate, $collabClaim->Updated);
        $this->assertEquals(0, $collabClaim->Extension);
    }

    public function testCollaborationClaimAndPrimaryExtend(): void
    {
        $this->seed(RolesTableSeeder::class);

        /** @var User $user */
        $user = User::factory()->create();
        $user->assignRole(Role::DEVELOPER);

        /** @var User $user2 */
        $user2 = User::factory()->create();
        $user2->assignRole(Role::DEVELOPER);

        /** @var Game $game */
        $game = $this->seedGame(withHash: false);

        // initial claim
        $claimDate = Carbon::now()->startOfSecond();
        Carbon::setTestNow($claimDate);

        $response = $this->actingAs($user)->postJson(route('achievement-set-claim.create', $game->id));

        $response->assertStatus(302); // redirect
        $response->assertRedirect('/'); // back() redirects to home when no source is provided
        $response->assertSessionHas('success', 'Claim created successfully');

        $claim = $game->achievementSetClaims()->first();
        $this->assertNotNull($claim);

        // collaboration claim
        $collabDate = $claimDate->clone()->addHours(2);
        Carbon::setTestNow($collabDate);

        Session::flush();
        $response = $this->actingAs($user2)->postJson(route('achievement-set-claim.create', $game->ID));

        $response->assertStatus(302); // redirect
        $response->assertRedirect('/'); // back() redirects to home when no source is provided
        $response->assertSessionHas('success', 'Claim created successfully');

        $collabClaim = $game->achievementSetClaims()->where('user_id', $user2->id)->first();
        $this->assertNotNull($collabClaim);
        $this->assertEquals($user2->id, $collabClaim->user_id);
        $this->assertEquals($game->id, $collabClaim->game_id);
        $this->assertEquals(ClaimType::Collaboration, $collabClaim->ClaimType);
        $this->assertEquals(ClaimSetType::NewSet, $collabClaim->SetType);
        $this->assertEquals(ClaimStatus::Active, $collabClaim->Status);
        $this->assertEquals(ClaimSpecial::None, $collabClaim->Special);
        $this->assertEquals($collabDate->clone()->addMonths(3), $collabClaim->Finished);
        $this->assertEquals($collabDate, $collabClaim->Created);
        $this->assertEquals($collabDate, $collabClaim->Updated);
        $this->assertEquals(0, $collabClaim->Extension);

        // extend primary claim
        $claimFinished = $claim->Finished;
        $extendDate = $claimDate->clone()->addWeeks(11);
        Carbon::setTestNow($extendDate);

        Session::flush();
        $response = $this->actingAs($user)->postJson(route('achievement-set-claim.create', $game->id));

        $response->assertStatus(302); // redirect
        $response->assertRedirect('/'); // back() redirects to home when no source is provided
        $response->assertSessionHas('success', 'Claim updated successfully');

        $claim->refresh();
        $this->assertEquals($user->id, $claim->user_id);
        $this->assertEquals($game->id, $claim->game_id);
        $this->assertEquals(ClaimType::Primary, $claim->ClaimType);
        $this->assertEquals(ClaimSetType::NewSet, $claim->SetType);
        $this->assertEquals(ClaimStatus::Active, $claim->Status);
        $this->assertEquals(ClaimSpecial::None, $claim->Special);
        $this->assertEquals($claimFinished->clone()->addMonths(3), $claim->Finished);
        $this->assertEquals($claimDate, $claim->Created);
        $this->assertEquals($extendDate, $claim->Updated);
        $this->assertEquals(1, $claim->Extension);

        // collab claim should be also be extended
        $collabClaim->refresh();
        $this->assertEquals($user2->id, $collabClaim->user_id);
        $this->assertEquals($game->id, $collabClaim->game_id);
        $this->assertEquals(ClaimType::Collaboration, $collabClaim->ClaimType);
        $this->assertEquals(ClaimSetType::NewSet, $collabClaim->SetType);
        $this->assertEquals(ClaimStatus::Active, $collabClaim->Status);
        $this->assertEquals(ClaimSpecial::None, $collabClaim->Special);
        $this->assertEquals($claimFinished->clone()->addMonths(3), $collabClaim->Finished);
        $this->assertEquals($collabDate, $collabClaim->Created);
        $this->assertEquals($extendDate, $collabClaim->Updated);
        $this->assertEquals(1, $collabClaim->Extension);
    }

    public function testPrimaryRevisionClaimAndComplete(): void
    {
        $this->seed(RolesTableSeeder::class);

        /** @var User $user */
        $user = User::factory()->create();
        $user->assignRole(Role::DEVELOPER);

        /** @var User $user2 */
        $user2 = User::factory()->create();
        $user2->assignRole(Role::DEVELOPER);

        /** @var Game $game */
        $game = $this->seedGame(withHash: false);
        $game->achievements_published = 40;
        $game->save();

        generateGameForumTopic($user2, $game->ID);

        // initial claim
        $claimDate = Carbon::now()->startOfSecond();
        Carbon::setTestNow($claimDate);

        $response = $this->actingAs($user)->postJson(route('achievement-set-claim.create', $game->id));

        $response->assertStatus(302); // redirect
        $response->assertRedirect('/'); // back() redirects to home when no source is provided
        $response->assertSessionHas('success', 'Claim created successfully');

        $claim = $game->achievementSetClaims()->first();
        $this->assertNotNull($claim);
        $this->assertEquals($user->id, $claim->user_id);
        $this->assertEquals($game->id, $claim->game_id);
        $this->assertEquals(ClaimType::Primary, $claim->ClaimType);
        $this->assertEquals(ClaimSetType::Revision, $claim->SetType);
        $this->assertEquals(ClaimStatus::Active, $claim->Status);
        $this->assertEquals(ClaimSpecial::None, $claim->Special);
        $this->assertEquals($claimDate->clone()->addMonths(3), $claim->Finished);
        $this->assertEquals($claimDate, $claim->Created);
        $this->assertEquals($claimDate, $claim->Updated);
        $this->assertEquals(0, $claim->Extension);

        // user should automatically be subscribed to the game wall
        $this->assertTrue($user->subscriptions()
            ->where('subject_type', SubscriptionSubjectType::GameWall)
            ->where('subject_id', $game->id)
            ->where('state', true)
            ->exists()
        );

        // new forum topic should not have been created by the user
        $game->refresh();
        $this->assertFalse(ForumTopicComment::where('ForumTopicID', $game->ForumTopicID)
            ->where('author_id', $user->ID)
            ->exists()
        );

        // user didn't create the forum topic, so they should be explicit subscribed
        $this->assertTrue($user->subscriptions()
            ->where('subject_type', SubscriptionSubjectType::ForumTopic)
            ->where('subject_id', $game->ForumTopicID)
            ->where('state', true)
            ->exists()
        );

        // complete claim
        $completeDate = $claimDate->clone()->addDays(7);
        Carbon::setTestNow($completeDate);

        $response = $this->actingAs($user)->postJson(route('achievement-set-claim.update', $claim->ID), [
            'status' => ClaimStatus::Complete,
        ]);

        $response->assertStatus(302); // redirect
        $response->assertRedirect('/'); // back() redirects to home when no source is provided
        $response->assertSessionHas('success', 'Claim updated successfully');

        $claim->refresh();
        $this->assertEquals($user->id, $claim->user_id);
        $this->assertEquals($game->id, $claim->game_id);
        $this->assertEquals(ClaimType::Primary, $claim->ClaimType);
        $this->assertEquals(ClaimSetType::Revision, $claim->SetType);
        $this->assertEquals(ClaimStatus::Complete, $claim->Status);
        $this->assertEquals(ClaimSpecial::None, $claim->Special);
        $this->assertEquals($completeDate, $claim->Finished);
        $this->assertEquals($claimDate, $claim->Created);
        $this->assertEquals($completeDate, $claim->Updated);
        $this->assertEquals(0, $claim->Extension);

        // attempt to drop claim
        $dropDate = $completeDate->clone()->addDays(23);
        Carbon::setTestNow($dropDate);

        $response = $this->actingAs($user)->postJson(route('achievement-set-claim.delete', $game->id));

        $response->assertStatus(302); // redirect
        $response->assertRedirect('/'); // back() redirects to home when no source is provided
        $response->assertSessionHas('error', 'You do not have a claim on this game.');

        $claim->refresh();
        $this->assertEquals($user->id, $claim->user_id);
        $this->assertEquals($game->id, $claim->game_id);
        $this->assertEquals(ClaimType::Primary, $claim->ClaimType);
        $this->assertEquals(ClaimSetType::Revision, $claim->SetType);
        $this->assertEquals(ClaimStatus::Complete, $claim->Status);
        $this->assertEquals(ClaimSpecial::None, $claim->Special);
        $this->assertEquals($completeDate, $claim->Finished);
        $this->assertEquals($claimDate, $claim->Created);
        $this->assertEquals($completeDate, $claim->Updated);
        $this->assertEquals(0, $claim->Extension);
    }

    public function testOwnRevisionClaimAndComplete(): void
    {
        $this->seed(RolesTableSeeder::class);

        /** @var User $user */
        $user = User::factory()->create();
        $user->assignRole(Role::DEVELOPER);

        /** @var Game $game */
        $game = $this->seedGame(withHash: false);
        Achievement::factory()->create(['GameID' => $game->id, 'user_id' => $user->id, 'Flags' => AchievementFlag::OfficialCore->value]);
        $game->achievements_published = 40;
        $game->save();

        // initial claim
        $claimDate = Carbon::now()->startOfSecond();
        Carbon::setTestNow($claimDate);

        $response = $this->actingAs($user)->postJson(route('achievement-set-claim.create', $game->id));

        $response->assertStatus(302); // redirect
        $response->assertRedirect('/'); // back() redirects to home when no source is provided
        $response->assertSessionHas('success', 'Claim created successfully');

        $claim = $game->achievementSetClaims()->first();
        $this->assertNotNull($claim);
        $this->assertEquals($user->id, $claim->user_id);
        $this->assertEquals($game->id, $claim->game_id);
        $this->assertEquals(ClaimType::Primary, $claim->ClaimType);
        $this->assertEquals(ClaimSetType::Revision, $claim->SetType);
        $this->assertEquals(ClaimStatus::Active, $claim->Status);
        $this->assertEquals(ClaimSpecial::OwnRevision, $claim->Special);
        $this->assertEquals($claimDate->clone()->addMonths(3), $claim->Finished);
        $this->assertEquals($claimDate, $claim->Created);
        $this->assertEquals($claimDate, $claim->Updated);
        $this->assertEquals(0, $claim->Extension);

        // complete claim
        $completeDate = $claimDate->clone()->addDays(7);
        Carbon::setTestNow($completeDate);

        $response = $this->actingAs($user)->postJson(route('achievement-set-claim.update', $claim->ID), [
            'status' => ClaimStatus::Complete,
        ]);

        $response->assertStatus(302); // redirect
        $response->assertRedirect('/'); // back() redirects to home when no source is provided
        $response->assertSessionHas('success', 'Claim updated successfully');

        $claim->refresh();
        $this->assertEquals($user->id, $claim->user_id);
        $this->assertEquals($game->id, $claim->game_id);
        $this->assertEquals(ClaimType::Primary, $claim->ClaimType);
        $this->assertEquals(ClaimSetType::Revision, $claim->SetType);
        $this->assertEquals(ClaimStatus::Complete, $claim->Status);
        $this->assertEquals(ClaimSpecial::OwnRevision, $claim->Special);
        $this->assertEquals($completeDate, $claim->Finished);
        $this->assertEquals($claimDate, $claim->Created);
        $this->assertEquals($completeDate, $claim->Updated);
        $this->assertEquals(0, $claim->Extension);

        // attempt to drop claim
        $dropDate = $completeDate->clone()->addDays(23);
        Carbon::setTestNow($dropDate);

        $response = $this->actingAs($user)->postJson(route('achievement-set-claim.delete', $game->id));

        $response->assertStatus(302); // redirect
        $response->assertRedirect('/'); // back() redirects to home when no source is provided
        $response->assertSessionHas('error', 'You do not have a claim on this game.');

        $claim->refresh();
        $this->assertEquals($user->id, $claim->user_id);
        $this->assertEquals($game->id, $claim->game_id);
        $this->assertEquals(ClaimType::Primary, $claim->ClaimType);
        $this->assertEquals(ClaimSetType::Revision, $claim->SetType);
        $this->assertEquals(ClaimStatus::Complete, $claim->Status);
        $this->assertEquals(ClaimSpecial::OwnRevision, $claim->Special);
        $this->assertEquals($completeDate, $claim->Finished);
        $this->assertEquals($claimDate, $claim->Created);
        $this->assertEquals($completeDate, $claim->Updated);
        $this->assertEquals(0, $claim->Extension);
    }

    public function testPrimaryClaimInReview(): void
    {
        $this->seed(RolesTableSeeder::class);

        /** @var User $user */
        $user = User::factory()->create();
        $user->assignRole(Role::DEVELOPER);

        /** @var Game $game */
        $game = $this->seedGame(withHash: false);

        // initial claim
        $claimDate = Carbon::now()->startOfSecond();
        Carbon::setTestNow($claimDate);

        $response = $this->actingAs($user)->postJson(route('achievement-set-claim.create', $game->id));

        $response->assertStatus(302); // redirect
        $response->assertRedirect('/'); // back() redirects to home when no source is provided
        $response->assertSessionHas('success', 'Claim created successfully');

        $claim = $game->achievementSetClaims()->first();
        $this->assertNotNull($claim);
        $this->assertEquals($user->id, $claim->user_id);
        $this->assertEquals($game->id, $claim->game_id);
        $this->assertEquals(ClaimType::Primary, $claim->ClaimType);
        $this->assertEquals(ClaimSetType::NewSet, $claim->SetType);
        $this->assertEquals(ClaimStatus::Active, $claim->Status);
        $this->assertEquals(ClaimSpecial::None, $claim->Special);
        $this->assertEquals($claimDate->clone()->addMonths(3), $claim->Finished);
        $this->assertEquals($claimDate, $claim->Created);
        $this->assertEquals($claimDate, $claim->Updated);
        $this->assertEquals(0, $claim->Extension);

        // change to review status
        $reviewDate = $claimDate->clone()->addWeeks(11);
        Carbon::setTestNow($reviewDate);

        $response = $this->actingAs($user)->postJson(route('achievement-set-claim.update', $claim->ID), [
            'status' => ClaimStatus::InReview,
        ]);

        $response->assertStatus(302); // redirect
        $response->assertRedirect('/'); // back() redirects to home when no source is provided
        $response->assertSessionHas('success', 'Claim updated successfully');

        $claim->refresh();
        $this->assertEquals($user->id, $claim->user_id);
        $this->assertEquals($game->id, $claim->game_id);
        $this->assertEquals(ClaimType::Primary, $claim->ClaimType);
        $this->assertEquals(ClaimSetType::NewSet, $claim->SetType);
        $this->assertEquals(ClaimStatus::InReview, $claim->Status);
        $this->assertEquals(ClaimSpecial::None, $claim->Special);
        $this->assertEquals($claimDate->clone()->addMonths(3), $claim->Finished);
        $this->assertEquals($claimDate, $claim->Created);
        $this->assertEquals($reviewDate, $claim->Updated);
        $this->assertEquals(0, $claim->Extension);

        // attempt to drop claim
        $dropDate = $reviewDate->clone()->addDays(2);
        Carbon::setTestNow($dropDate);

        $response = $this->actingAs($user)->postJson(route('achievement-set-claim.delete', $game->id));

        $response->assertStatus(302); // redirect
        $response->assertRedirect('/'); // back() redirects to home when no source is provided
        $response->assertSessionHas('error', 'You do not have a claim on this game.');

        // extend claim
        $claimFinished = $claim->Finished;
        $extendDate = $claimDate->clone()->addDays(30 + 30 + 27);
        Carbon::setTestNow($extendDate);

        $response = $this->actingAs($user)->postJson(route('achievement-set-claim.create', $game->id));

        $response->assertStatus(302); // redirect
        $response->assertRedirect('/'); // back() redirects to home when no source is provided
        $response->assertSessionHas('success', 'Claim updated successfully');

        $claim->refresh();
        $this->assertEquals($user->id, $claim->user_id);
        $this->assertEquals($game->id, $claim->game_id);
        $this->assertEquals(ClaimType::Primary, $claim->ClaimType);
        $this->assertEquals(ClaimSetType::NewSet, $claim->SetType);
        $this->assertEquals(ClaimStatus::InReview, $claim->Status);
        $this->assertEquals(ClaimSpecial::None, $claim->Special);
        $this->assertEquals($claimFinished->clone()->addMonths(3), $claim->Finished);
        $this->assertEquals($claimDate, $claim->Created);
        $this->assertEquals($extendDate, $claim->Updated);
        $this->assertEquals(1, $claim->Extension);

        // end review
        $reviewDate = $extendDate->clone()->addWeeks(1);
        Carbon::setTestNow($reviewDate);

        $response = $this->actingAs($user)->postJson(route('achievement-set-claim.update', $claim->ID), [
            'status' => ClaimStatus::Active,
        ]);

        $claim->refresh();
        $this->assertEquals($user->id, $claim->user_id);
        $this->assertEquals($game->id, $claim->game_id);
        $this->assertEquals(ClaimType::Primary, $claim->ClaimType);
        $this->assertEquals(ClaimSetType::NewSet, $claim->SetType);
        $this->assertEquals(ClaimStatus::Active, $claim->Status);
        $this->assertEquals(ClaimSpecial::None, $claim->Special);
        $this->assertEquals($claimFinished->clone()->addMonths(3), $claim->Finished);
        $this->assertEquals($claimDate, $claim->Created);
        $this->assertEquals($reviewDate, $claim->Updated);
        $this->assertEquals(1, $claim->Extension);
    }
}

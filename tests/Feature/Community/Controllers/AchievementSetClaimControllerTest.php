<?php

declare(strict_types=1);

namespace Tests\Feature\Community\Controllers;

use App\Community\Enums\ClaimSetType;
use App\Community\Enums\ClaimSpecial;
use App\Community\Enums\ClaimStatus;
use App\Community\Enums\ClaimType;
use App\Community\Enums\SubscriptionSubjectType;
use App\Models\Achievement;
use App\Models\Forum;
use App\Models\ForumTopicComment;
use App\Models\Game;
use App\Models\Role;
use App\Models\Subscription;
use App\Models\User;
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

        Forum::factory()->create(['id' => 10, 'title' => 'Default']);

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
        $this->assertEquals(ClaimType::Primary, $claim->claim_type);
        $this->assertEquals(ClaimSetType::NewSet, $claim->set_type);
        $this->assertEquals(ClaimStatus::Active, $claim->status);
        $this->assertEquals(ClaimSpecial::None, $claim->special_type);
        $this->assertEquals($claimDate->clone()->addMonths(3), $claim->finished_at);
        $this->assertEquals($claimDate, $claim->created_at);
        $this->assertEquals($claimDate, $claim->updated_at);
        $this->assertEquals(0, $claim->extensions_count);

        // user should automatically be subscribed to the game wall
        $this->assertTrue($user->subscriptions()
            ->where('subject_type', SubscriptionSubjectType::GameWall)
            ->where('subject_id', $game->id)
            ->where('state', true)
            ->exists()
        );

        // forum topic should automatically be created by the user
        $game->refresh();
        $this->assertGreaterThan(0, $game->forum_topic_id);

        $this->assertTrue(ForumTopicComment::where('forum_topic_id', $game->forum_topic_id)
            ->where('author_id', $user->id)
            ->exists()
        );

        // implicit subscription to forum topic. don't need an explicit one
        $this->assertFalse($user->subscriptions()
            ->where('subject_type', SubscriptionSubjectType::ForumTopic)
            ->where('subject_id', $game->forum_topic_id)
            ->exists()
        );

        // add official achievements so the claim can be completed
        $this->seedAchievements(amount: 6, game: $game);

        // complete claim
        $completeDate = $claimDate->clone()->addDays(41);
        Carbon::setTestNow($completeDate);

        $response = $this->actingAs($user)->postJson(route('achievement-set-claim.update', $claim->id), [
            'status' => ClaimStatus::Complete->value,
        ]);

        $response->assertStatus(302); // redirect
        $response->assertRedirect('/'); // back() redirects to home when no source is provided
        $response->assertSessionHas('success', 'Claim updated successfully');

        $claim->refresh();
        $this->assertEquals($user->id, $claim->user_id);
        $this->assertEquals($game->id, $claim->game_id);
        $this->assertEquals(ClaimType::Primary, $claim->claim_type);
        $this->assertEquals(ClaimSetType::NewSet, $claim->set_type);
        $this->assertEquals(ClaimStatus::Complete, $claim->status);
        $this->assertEquals(ClaimSpecial::None, $claim->special_type);
        $this->assertEquals($completeDate, $claim->finished_at);
        $this->assertEquals($claimDate, $claim->created_at);
        $this->assertEquals($completeDate, $claim->updated_at);
        $this->assertEquals(0, $claim->extensions_count);

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
        $this->assertEquals(ClaimType::Primary, $claim->claim_type);
        $this->assertEquals(ClaimSetType::NewSet, $claim->set_type);
        $this->assertEquals(ClaimStatus::Complete, $claim->status);
        $this->assertEquals(ClaimSpecial::None, $claim->special_type);
        $this->assertEquals($completeDate, $claim->finished_at);
        $this->assertEquals($claimDate, $claim->created_at);
        $this->assertEquals($completeDate, $claim->updated_at);
        $this->assertEquals(0, $claim->extensions_count);
    }

    public function testExtendAndDropPrimaryClaim(): void
    {
        $this->seed(RolesTableSeeder::class);

        /** @var User $user */
        $user = User::factory()->create();
        $user->assignRole(Role::DEVELOPER);

        /** @var Game $game */
        $game = $this->seedGame(withHash: false);

        Forum::factory()->create(['id' => 10, 'title' => 'Default']);

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
        $this->assertEquals(ClaimType::Primary, $claim->claim_type);
        $this->assertEquals(ClaimSetType::NewSet, $claim->set_type);
        $this->assertEquals(ClaimStatus::Active, $claim->status);
        $this->assertEquals(ClaimSpecial::None, $claim->special_type);
        $this->assertEquals($claimDate->clone()->addMonths(3), $claim->finished_at);
        $this->assertEquals($claimDate, $claim->created_at);
        $this->assertEquals($claimDate, $claim->updated_at);
        $this->assertEquals(0, $claim->extensions_count);

        // extend claim
        $claimFinished = $claim->finished_at;
        $extendDate = $claimDate->clone()->addWeeks(11);
        Carbon::setTestNow($extendDate);

        $response = $this->actingAs($user)->postJson(route('achievement-set-claim.create', $game->id));

        $response->assertStatus(302); // redirect
        $response->assertRedirect('/'); // back() redirects to home when no source is provided
        $response->assertSessionHas('success', 'Claim updated successfully');

        $claim->refresh();
        $this->assertEquals($user->id, $claim->user_id);
        $this->assertEquals($game->id, $claim->game_id);
        $this->assertEquals(ClaimType::Primary, $claim->claim_type);
        $this->assertEquals(ClaimSetType::NewSet, $claim->set_type);
        $this->assertEquals(ClaimStatus::Active, $claim->status);
        $this->assertEquals(ClaimSpecial::None, $claim->special_type);
        $this->assertEquals($claimFinished->clone()->addMonths(3), $claim->finished_at);
        $this->assertEquals($claimDate, $claim->created_at);
        $this->assertEquals($extendDate, $claim->updated_at);
        $this->assertEquals(1, $claim->extensions_count);

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
        $this->assertEquals(ClaimType::Primary, $claim->claim_type);
        $this->assertEquals(ClaimSetType::NewSet, $claim->set_type);
        $this->assertEquals(ClaimStatus::Dropped, $claim->status);
        $this->assertEquals(ClaimSpecial::None, $claim->special_type);
        $this->assertEquals($dropDate, $claim->finished_at);
        $this->assertEquals($claimDate, $claim->created_at);
        $this->assertEquals($dropDate, $claim->updated_at);
        $this->assertEquals(1, $claim->extensions_count);

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
        $this->assertEquals(ClaimType::Primary, $claim->claim_type);
        $this->assertEquals(ClaimSetType::NewSet, $claim->set_type);
        $this->assertEquals(ClaimStatus::Dropped, $claim->status);
        $this->assertEquals(ClaimSpecial::None, $claim->special_type);
        $this->assertEquals($dropDate, $claim->finished_at);
        $this->assertEquals($claimDate, $claim->created_at);
        $this->assertEquals($dropDate, $claim->updated_at);
        $this->assertEquals(1, $claim->extensions_count);

        // re-claim
        $reclaimDate = Carbon::now()->startOfSecond();
        Carbon::setTestNow($reclaimDate);

        $response = $this->actingAs($user)->postJson(route('achievement-set-claim.create', $game->id));

        $response->assertStatus(302); // redirect
        $response->assertRedirect('/'); // back() redirects to home when no source is provided
        $response->assertSessionHas('success', 'Claim created successfully');

        $newClaim = $game->achievementSetClaims()->active()->first();
        $this->assertNotNull($newClaim);
        $this->assertNotEquals($newClaim->id, $claim->id);
        $this->assertEquals($user->id, $newClaim->user_id);
        $this->assertEquals($game->id, $newClaim->game_id);
        $this->assertEquals(ClaimType::Primary, $newClaim->claim_type);
        $this->assertEquals(ClaimSetType::NewSet, $newClaim->set_type);
        $this->assertEquals(ClaimStatus::Active, $newClaim->status);
        $this->assertEquals(ClaimSpecial::None, $newClaim->special_type);
        $this->assertEquals($reclaimDate->clone()->addMonths(3), $newClaim->finished_at);
        $this->assertEquals($reclaimDate, $newClaim->created_at);
        $this->assertEquals($reclaimDate, $newClaim->updated_at);
        $this->assertEquals(0, $newClaim->extensions_count);
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

        Forum::factory()->create(['id' => 10, 'title' => 'Default']);

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
        $response = $this->actingAs($user2)->postJson(route('achievement-set-claim.create', $game->id));

        $response->assertStatus(302); // redirect
        $response->assertRedirect('/'); // back() redirects to home when no source is provided
        $response->assertSessionHas('success', 'Claim created successfully');

        $collabClaim = $game->achievementSetClaims()->where('user_id', $user2->id)->first();
        $this->assertNotNull($collabClaim);
        $this->assertEquals($user2->id, $collabClaim->user_id);
        $this->assertEquals($game->id, $collabClaim->game_id);
        $this->assertEquals(ClaimType::Collaboration, $collabClaim->claim_type);
        $this->assertEquals(ClaimSetType::NewSet, $collabClaim->set_type);
        $this->assertEquals(ClaimStatus::Active, $collabClaim->status);
        $this->assertEquals(ClaimSpecial::None, $collabClaim->special_type);
        $this->assertEquals($collabDate->clone()->addMonths(3), $collabClaim->finished_at);
        $this->assertEquals($collabDate, $collabClaim->created_at);
        $this->assertEquals($collabDate, $collabClaim->updated_at);
        $this->assertEquals(0, $collabClaim->extensions_count);

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
        $this->assertEquals(ClaimType::Collaboration, $collabClaim->claim_type);
        $this->assertEquals(ClaimSetType::NewSet, $collabClaim->set_type);
        $this->assertEquals(ClaimStatus::Dropped, $collabClaim->status);
        $this->assertEquals(ClaimSpecial::None, $collabClaim->special_type);
        $this->assertEquals($dropDate, $collabClaim->finished_at);
        $this->assertEquals($collabDate, $collabClaim->created_at);
        $this->assertEquals($dropDate, $collabClaim->updated_at);
        $this->assertEquals(0, $collabClaim->extensions_count);

        // primary claim is unaffected
        $claim = $game->achievementSetClaims()->where('user_id', $user->id)->first();
        $this->assertNotNull($claim);
        $this->assertEquals($user->id, $claim->user_id);
        $this->assertEquals($game->id, $claim->game_id);
        $this->assertEquals(ClaimType::Primary, $claim->claim_type);
        $this->assertEquals(ClaimSetType::NewSet, $claim->set_type);
        $this->assertEquals(ClaimStatus::Active, $claim->status);
        $this->assertEquals(ClaimSpecial::None, $claim->special_type);
        $this->assertEquals($claimDate->clone()->addMonths(3), $claim->finished_at);
        $this->assertEquals($claimDate, $claim->created_at);
        $this->assertEquals($claimDate, $claim->updated_at);
        $this->assertEquals(0, $claim->extensions_count);
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

        Forum::factory()->create(['id' => 10, 'title' => 'Default']);

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
        $response = $this->actingAs($user2)->postJson(route('achievement-set-claim.create', $game->id));

        $response->assertStatus(302); // redirect
        $response->assertRedirect('/'); // back() redirects to home when no source is provided
        $response->assertSessionHas('success', 'Claim created successfully');

        $collabClaim = $game->achievementSetClaims()->where('user_id', $user2->id)->first();
        $this->assertNotNull($collabClaim);
        $this->assertEquals($user2->id, $collabClaim->user_id);
        $this->assertEquals($game->id, $collabClaim->game_id);
        $this->assertEquals(ClaimType::Collaboration, $collabClaim->claim_type);
        $this->assertEquals(ClaimSetType::NewSet, $collabClaim->set_type);
        $this->assertEquals(ClaimStatus::Active, $collabClaim->status);
        $this->assertEquals(ClaimSpecial::None, $collabClaim->special_type);
        $this->assertEquals($collabDate->clone()->addMonths(3), $collabClaim->finished_at);
        $this->assertEquals($collabDate, $collabClaim->created_at);
        $this->assertEquals($collabDate, $collabClaim->updated_at);
        $this->assertEquals(0, $collabClaim->extensions_count);

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
        $this->assertEquals(ClaimType::Primary, $claim->claim_type);
        $this->assertEquals(ClaimSetType::NewSet, $claim->set_type);
        $this->assertEquals(ClaimStatus::Dropped, $claim->status);
        $this->assertEquals(ClaimSpecial::None, $claim->special_type);
        $this->assertEquals($dropDate, $claim->finished_at);
        $this->assertEquals($claimDate, $claim->created_at);
        $this->assertEquals($dropDate, $claim->updated_at);
        $this->assertEquals(0, $claim->extensions_count);

        // collab claim should be promoted to primary claim
        $collabClaim->refresh();
        $this->assertEquals($user2->id, $collabClaim->user_id);
        $this->assertEquals($game->id, $collabClaim->game_id);
        $this->assertEquals(ClaimType::Primary, $collabClaim->claim_type);
        $this->assertEquals(ClaimSetType::NewSet, $collabClaim->set_type);
        $this->assertEquals(ClaimStatus::Active, $collabClaim->status);
        $this->assertEquals(ClaimSpecial::None, $collabClaim->special_type);
        $this->assertEquals($collabDate->clone()->addMonths(3), $collabClaim->finished_at);
        $this->assertEquals($collabDate, $collabClaim->created_at);
        $this->assertEquals($dropDate, $collabClaim->updated_at);
        $this->assertEquals(0, $collabClaim->extensions_count);
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

        Forum::factory()->create(['id' => 10, 'title' => 'Default']);

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
        $response = $this->actingAs($user2)->postJson(route('achievement-set-claim.create', $game->id));

        $response->assertStatus(302); // redirect
        $response->assertRedirect('/'); // back() redirects to home when no source is provided
        $response->assertSessionHas('success', 'Claim created successfully');

        $collabClaim = $game->achievementSetClaims()->where('user_id', $user2->id)->first();
        $this->assertNotNull($collabClaim);
        $this->assertEquals($user2->id, $collabClaim->user_id);
        $this->assertEquals($game->id, $collabClaim->game_id);
        $this->assertEquals(ClaimType::Collaboration, $collabClaim->claim_type);
        $this->assertEquals(ClaimSetType::NewSet, $collabClaim->set_type);
        $this->assertEquals(ClaimStatus::Active, $collabClaim->status);
        $this->assertEquals(ClaimSpecial::None, $collabClaim->special_type);
        $this->assertEquals($collabDate->clone()->addMonths(3), $collabClaim->finished_at);
        $this->assertEquals($collabDate, $collabClaim->created_at);
        $this->assertEquals($collabDate, $collabClaim->updated_at);
        $this->assertEquals(0, $collabClaim->extensions_count);

        // add official achievements so the claim can be completed
        $this->seedAchievements(amount: 6, game: $game);

        // complete primary claim
        $completeDate = $collabDate->clone()->addDays(23);
        Carbon::setTestNow($completeDate);

        Session::flush();
        $response = $this->actingAs($user)->postJson(route('achievement-set-claim.update', $game->id), ['status' => ClaimStatus::Complete->value]);

        $response->assertStatus(302); // redirect
        $response->assertRedirect('/'); // back() redirects to home when no source is provided
        $response->assertSessionHas('success', 'Claim updated successfully');

        $claim->refresh();
        $this->assertEquals($user->id, $claim->user_id);
        $this->assertEquals($game->id, $claim->game_id);
        $this->assertEquals(ClaimType::Primary, $claim->claim_type);
        $this->assertEquals(ClaimSetType::NewSet, $claim->set_type);
        $this->assertEquals(ClaimStatus::Complete, $claim->status);
        $this->assertEquals(ClaimSpecial::None, $claim->special_type);
        $this->assertEquals($completeDate, $claim->finished_at);
        $this->assertEquals($claimDate, $claim->created_at);
        $this->assertEquals($completeDate, $claim->updated_at);
        $this->assertEquals(0, $claim->extensions_count);

        // collab claim should be also be completed
        $collabClaim->refresh();
        $this->assertEquals($user2->id, $collabClaim->user_id);
        $this->assertEquals($game->id, $collabClaim->game_id);
        $this->assertEquals(ClaimType::Collaboration, $collabClaim->claim_type);
        $this->assertEquals(ClaimSetType::NewSet, $collabClaim->set_type);
        $this->assertEquals(ClaimStatus::Complete, $collabClaim->status);
        $this->assertEquals(ClaimSpecial::None, $collabClaim->special_type);
        $this->assertEquals($completeDate, $collabClaim->finished_at);
        $this->assertEquals($collabDate, $collabClaim->created_at);
        $this->assertEquals($completeDate, $collabClaim->updated_at);
        $this->assertEquals(0, $collabClaim->extensions_count);
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

        Forum::factory()->create(['id' => 10, 'title' => 'Default']);

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
        $response = $this->actingAs($user2)->postJson(route('achievement-set-claim.create', $game->id));

        $response->assertStatus(302); // redirect
        $response->assertRedirect('/'); // back() redirects to home when no source is provided
        $response->assertSessionHas('success', 'Claim created successfully');

        $collabClaim = $game->achievementSetClaims()->where('user_id', $user2->id)->first();
        $this->assertNotNull($collabClaim);
        $this->assertEquals($user2->id, $collabClaim->user_id);
        $this->assertEquals($game->id, $collabClaim->game_id);
        $this->assertEquals(ClaimType::Collaboration, $collabClaim->claim_type);
        $this->assertEquals(ClaimSetType::NewSet, $collabClaim->set_type);
        $this->assertEquals(ClaimStatus::Active, $collabClaim->status);
        $this->assertEquals(ClaimSpecial::None, $collabClaim->special_type);
        $this->assertEquals($collabDate->clone()->addMonths(3), $collabClaim->finished_at);
        $this->assertEquals($collabDate, $collabClaim->created_at);
        $this->assertEquals($collabDate, $collabClaim->updated_at);
        $this->assertEquals(0, $collabClaim->extensions_count);

        // extend primary claim
        $claimFinished = $claim->finished_at;
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
        $this->assertEquals(ClaimType::Primary, $claim->claim_type);
        $this->assertEquals(ClaimSetType::NewSet, $claim->set_type);
        $this->assertEquals(ClaimStatus::Active, $claim->status);
        $this->assertEquals(ClaimSpecial::None, $claim->special_type);
        $this->assertEquals($claimFinished->clone()->addMonths(3), $claim->finished_at);
        $this->assertEquals($claimDate, $claim->created_at);
        $this->assertEquals($extendDate, $claim->updated_at);
        $this->assertEquals(1, $claim->extensions_count);

        // collab claim should be also be extended
        $collabClaim->refresh();
        $this->assertEquals($user2->id, $collabClaim->user_id);
        $this->assertEquals($game->id, $collabClaim->game_id);
        $this->assertEquals(ClaimType::Collaboration, $collabClaim->claim_type);
        $this->assertEquals(ClaimSetType::NewSet, $collabClaim->set_type);
        $this->assertEquals(ClaimStatus::Active, $collabClaim->status);
        $this->assertEquals(ClaimSpecial::None, $collabClaim->special_type);
        $this->assertEquals($claimFinished->clone()->addMonths(3), $collabClaim->finished_at);
        $this->assertEquals($collabDate, $collabClaim->created_at);
        $this->assertEquals($extendDate, $collabClaim->updated_at);
        $this->assertEquals(1, $collabClaim->extensions_count);
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
        $this->seedAchievements(amount: 40, game: $game);

        Forum::factory()->create(['id' => 10, 'title' => 'Default']);

        generateGameForumTopic($user2, $game->id);

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
        $this->assertEquals(ClaimType::Primary, $claim->claim_type);
        $this->assertEquals(ClaimSetType::Revision, $claim->set_type);
        $this->assertEquals(ClaimStatus::Active, $claim->status);
        $this->assertEquals(ClaimSpecial::None, $claim->special_type);
        $this->assertEquals($claimDate->clone()->addMonths(3), $claim->finished_at);
        $this->assertEquals($claimDate, $claim->created_at);
        $this->assertEquals($claimDate, $claim->updated_at);
        $this->assertEquals(0, $claim->extensions_count);

        // user should automatically be subscribed to the game wall
        $this->assertTrue($user->subscriptions()
            ->where('subject_type', SubscriptionSubjectType::GameWall)
            ->where('subject_id', $game->id)
            ->where('state', true)
            ->exists()
        );

        // new forum topic should not have been created by the user
        $game->refresh();
        $this->assertFalse(ForumTopicComment::where('forum_topic_id', $game->forum_topic_id)
            ->where('author_id', $user->id)
            ->exists()
        );

        // user didn't create the forum topic, so they should be explicit subscribed
        $this->assertTrue($user->subscriptions()
            ->where('subject_type', SubscriptionSubjectType::ForumTopic)
            ->where('subject_id', $game->forum_topic_id)
            ->where('state', true)
            ->exists()
        );

        // complete claim
        $completeDate = $claimDate->clone()->addDays(7);
        Carbon::setTestNow($completeDate);

        $response = $this->actingAs($user)->postJson(route('achievement-set-claim.update', $claim->id), [
            'status' => ClaimStatus::Complete->value,
        ]);

        $response->assertStatus(302); // redirect
        $response->assertRedirect('/'); // back() redirects to home when no source is provided
        $response->assertSessionHas('success', 'Claim updated successfully');

        $claim->refresh();
        $this->assertEquals($user->id, $claim->user_id);
        $this->assertEquals($game->id, $claim->game_id);
        $this->assertEquals(ClaimType::Primary, $claim->claim_type);
        $this->assertEquals(ClaimSetType::Revision, $claim->set_type);
        $this->assertEquals(ClaimStatus::Complete, $claim->status);
        $this->assertEquals(ClaimSpecial::None, $claim->special_type);
        $this->assertEquals($completeDate, $claim->finished_at);
        $this->assertEquals($claimDate, $claim->created_at);
        $this->assertEquals($completeDate, $claim->updated_at);
        $this->assertEquals(0, $claim->extensions_count);

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
        $this->assertEquals(ClaimType::Primary, $claim->claim_type);
        $this->assertEquals(ClaimSetType::Revision, $claim->set_type);
        $this->assertEquals(ClaimStatus::Complete, $claim->status);
        $this->assertEquals(ClaimSpecial::None, $claim->special_type);
        $this->assertEquals($completeDate, $claim->finished_at);
        $this->assertEquals($claimDate, $claim->created_at);
        $this->assertEquals($completeDate, $claim->updated_at);
        $this->assertEquals(0, $claim->extensions_count);
    }

    public function testOwnRevisionClaimAndComplete(): void
    {
        $this->seed(RolesTableSeeder::class);

        /** @var User $user */
        $user = User::factory()->create();
        $user->assignRole(Role::DEVELOPER);

        /** @var Game $game */
        $game = $this->seedGame(withHash: false);
        Achievement::factory()->promoted()->create(['game_id' => $game->id, 'user_id' => $user->id]);
        $game->achievements_published = 40;
        $game->save();

        Forum::factory()->create(['id' => 10, 'title' => 'Default']);

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
        $this->assertEquals(ClaimType::Primary, $claim->claim_type);
        $this->assertEquals(ClaimSetType::Revision, $claim->set_type);
        $this->assertEquals(ClaimStatus::Active, $claim->status);
        $this->assertEquals(ClaimSpecial::OwnRevision, $claim->special_type);
        $this->assertEquals($claimDate->clone()->addMonths(3), $claim->finished_at);
        $this->assertEquals($claimDate, $claim->created_at);
        $this->assertEquals($claimDate, $claim->updated_at);
        $this->assertEquals(0, $claim->extensions_count);

        // complete claim
        $completeDate = $claimDate->clone()->addDays(7);
        Carbon::setTestNow($completeDate);

        $response = $this->actingAs($user)->postJson(route('achievement-set-claim.update', $claim->id), [
            'status' => ClaimStatus::Complete->value,
        ]);

        $response->assertStatus(302); // redirect
        $response->assertRedirect('/'); // back() redirects to home when no source is provided
        $response->assertSessionHas('success', 'Claim updated successfully');

        $claim->refresh();
        $this->assertEquals($user->id, $claim->user_id);
        $this->assertEquals($game->id, $claim->game_id);
        $this->assertEquals(ClaimType::Primary, $claim->claim_type);
        $this->assertEquals(ClaimSetType::Revision, $claim->set_type);
        $this->assertEquals(ClaimStatus::Complete, $claim->status);
        $this->assertEquals(ClaimSpecial::OwnRevision, $claim->special_type);
        $this->assertEquals($completeDate, $claim->finished_at);
        $this->assertEquals($claimDate, $claim->created_at);
        $this->assertEquals($completeDate, $claim->updated_at);
        $this->assertEquals(0, $claim->extensions_count);

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
        $this->assertEquals(ClaimType::Primary, $claim->claim_type);
        $this->assertEquals(ClaimSetType::Revision, $claim->set_type);
        $this->assertEquals(ClaimStatus::Complete, $claim->status);
        $this->assertEquals(ClaimSpecial::OwnRevision, $claim->special_type);
        $this->assertEquals($completeDate, $claim->finished_at);
        $this->assertEquals($claimDate, $claim->created_at);
        $this->assertEquals($completeDate, $claim->updated_at);
        $this->assertEquals(0, $claim->extensions_count);
    }

    public function testPrimaryClaimInReview(): void
    {
        $this->seed(RolesTableSeeder::class);

        /** @var User $codeReviewer */
        $codeReviewer = User::factory()->create();
        $codeReviewer->assignRole(Role::CODE_REVIEWER);

        /** @var User $juniorDeveloper */
        $juniorDeveloper = User::factory()->create();
        $juniorDeveloper->assignRole(Role::DEVELOPER_JUNIOR);

        /** @var Game $game */
        $game = $this->seedGame(withHash: false);

        Forum::factory()->create(['id' => 10, 'title' => 'Default']);

        // create a forum topic for the game (required for junior devs on new sets)
        generateGameForumTopic($codeReviewer, $game->id);

        // initial claim
        $claimDate = Carbon::now()->startOfSecond();
        Carbon::setTestNow($claimDate);

        $response = $this->actingAs($juniorDeveloper)->postJson(route('achievement-set-claim.create', $game->id));

        $response->assertStatus(302); // redirect
        $response->assertRedirect('/'); // back() redirects to home when no source is provided
        $response->assertSessionHas('success', 'Claim created successfully');

        $claim = $game->achievementSetClaims()->first();
        $this->assertNotNull($claim);
        $this->assertEquals($juniorDeveloper->id, $claim->user_id);
        $this->assertEquals($game->id, $claim->game_id);
        $this->assertEquals(ClaimType::Primary, $claim->claim_type);
        $this->assertEquals(ClaimSetType::NewSet, $claim->set_type);
        $this->assertEquals(ClaimStatus::Active, $claim->status);
        $this->assertEquals(ClaimSpecial::None, $claim->special_type);
        $this->assertEquals($claimDate->clone()->addMonths(3), $claim->finished_at);
        $this->assertEquals($claimDate, $claim->created_at);
        $this->assertEquals($claimDate, $claim->updated_at);
        $this->assertEquals(0, $claim->extensions_count);

        // change to review status
        $reviewDate = $claimDate->clone()->addWeeks(11);
        Carbon::setTestNow($reviewDate);

        Session::flush();
        $response = $this->actingAs($codeReviewer)->postJson(route('achievement-set-claim.update', $claim->id), [
            'status' => ClaimStatus::InReview->value,
        ]);

        $response->assertStatus(302); // redirect
        $response->assertRedirect('/'); // back() redirects to home when no source is provided
        $response->assertSessionHas('success', 'Claim updated successfully');

        $claim->refresh();
        $this->assertEquals($juniorDeveloper->id, $claim->user_id);
        $this->assertEquals($game->id, $claim->game_id);
        $this->assertEquals(ClaimType::Primary, $claim->claim_type);
        $this->assertEquals(ClaimSetType::NewSet, $claim->set_type);
        $this->assertEquals(ClaimStatus::InReview, $claim->status);
        $this->assertEquals(ClaimSpecial::None, $claim->special_type);
        $this->assertEquals($claimDate->clone()->addMonths(3), $claim->finished_at);
        $this->assertEquals($claimDate, $claim->created_at);
        $this->assertEquals($reviewDate, $claim->updated_at);
        $this->assertEquals(0, $claim->extensions_count);

        // attempt to drop claim
        $dropDate = $reviewDate->clone()->addDays(2);
        Carbon::setTestNow($dropDate);

        Session::flush();
        $response = $this->actingAs($juniorDeveloper)->postJson(route('achievement-set-claim.delete', $game->id));

        $response->assertStatus(302); // redirect
        $response->assertRedirect('/'); // back() redirects to home when no source is provided
        $response->assertSessionHas('error', 'You do not have a claim on this game.');

        // extend claim
        $claimFinished = $claim->finished_at;
        $extendDate = $claimDate->clone()->addDays(30 + 30 + 27);
        Carbon::setTestNow($extendDate);

        $response = $this->actingAs($juniorDeveloper)->postJson(route('achievement-set-claim.create', $game->id));

        $response->assertStatus(302); // redirect
        $response->assertRedirect('/'); // back() redirects to home when no source is provided
        $response->assertSessionHas('success', 'Claim updated successfully');

        $claim->refresh();
        $this->assertEquals($juniorDeveloper->id, $claim->user_id);
        $this->assertEquals($game->id, $claim->game_id);
        $this->assertEquals(ClaimType::Primary, $claim->claim_type);
        $this->assertEquals(ClaimSetType::NewSet, $claim->set_type);
        $this->assertEquals(ClaimStatus::InReview, $claim->status);
        $this->assertEquals(ClaimSpecial::None, $claim->special_type);
        $this->assertEquals($claimFinished->clone()->addMonths(3), $claim->finished_at);
        $this->assertEquals($claimDate, $claim->created_at);
        $this->assertEquals($extendDate, $claim->updated_at);
        $this->assertEquals(1, $claim->extensions_count);

        // end review
        $reviewDate = $extendDate->clone()->addWeeks(1);
        Carbon::setTestNow($reviewDate);

        Session::flush();
        $response = $this->actingAs($codeReviewer)->postJson(route('achievement-set-claim.update', $claim->id), [
            'status' => ClaimStatus::Active->value,
        ]);

        $claim->refresh();
        $this->assertEquals($juniorDeveloper->id, $claim->user_id);
        $this->assertEquals($game->id, $claim->game_id);
        $this->assertEquals(ClaimType::Primary, $claim->claim_type);
        $this->assertEquals(ClaimSetType::NewSet, $claim->set_type);
        $this->assertEquals(ClaimStatus::Active, $claim->status);
        $this->assertEquals(ClaimSpecial::None, $claim->special_type);
        $this->assertEquals($claimFinished->clone()->addMonths(3), $claim->finished_at);
        $this->assertEquals($claimDate, $claim->created_at);
        $this->assertEquals($reviewDate, $claim->updated_at);
        $this->assertEquals(1, $claim->extensions_count);
    }

    public function testModeratorCanReactivateCompletedClaim(): void
    {
        $this->seed(RolesTableSeeder::class);

        /** @var User $developer */
        $developer = User::factory()->create();
        $developer->assignRole(Role::DEVELOPER);

        /** @var User $moderator */
        $moderator = User::factory()->create();
        $moderator->assignRole(Role::MODERATOR);

        /** @var Game $game */
        $game = $this->seedGame(withHash: false);

        Forum::factory()->create(['id' => 10, 'title' => 'Default']);

        // ... developer creates a claim ...
        $claimDate = Carbon::now()->startOfSecond();
        Carbon::setTestNow($claimDate);

        $response = $this->actingAs($developer)->postJson(route('achievement-set-claim.create', $game->id));
        $response->assertStatus(302);
        $response->assertSessionHas('success', 'Claim created successfully');

        $claim = $game->achievementSetClaims()->first();
        $this->assertNotNull($claim);
        $this->assertEquals(ClaimStatus::Active, $claim->status);

        // ... add achievements so the claim can be completed ...
        $this->seedAchievements(amount: 6, game: $game);

        // ... developer completes the claim ...
        $completeDate = $claimDate->clone()->addDays(30);
        Carbon::setTestNow($completeDate);

        Session::flush();
        $response = $this->actingAs($developer)->postJson(route('achievement-set-claim.update', $claim->id), [
            'status' => ClaimStatus::Complete,
        ]);
        $response->assertStatus(302);
        $response->assertSessionHas('success', 'Claim updated successfully');

        $claim->refresh();
        $this->assertEquals(ClaimStatus::Complete, $claim->status);

        // ... moderator reactivates the claim ...
        $reactivateDate = $completeDate->clone()->addDays(7);
        Carbon::setTestNow($reactivateDate);

        Session::flush();
        $response = $this->actingAs($moderator)->postJson(route('achievement-set-claim.update', $claim->id), [
            'status' => ClaimStatus::Active,
        ]);

        $response->assertStatus(302);
        $response->assertSessionHas('success', 'Claim updated successfully');

        $claim->refresh();
        $this->assertEquals(ClaimStatus::Active, $claim->status);
        $this->assertEquals($reactivateDate, $claim->updated_at);
    }

    public function testModeratorCanSetReactivatedClaimBackToComplete(): void
    {
        $this->seed(RolesTableSeeder::class);

        /** @var User $developer */
        $developer = User::factory()->create();
        $developer->assignRole(Role::DEVELOPER);

        /** @var User $moderator */
        $moderator = User::factory()->create();
        $moderator->assignRole(Role::MODERATOR);

        /** @var Game $game */
        $game = $this->seedGame(withHash: false);

        Forum::factory()->create(['id' => 10, 'title' => 'Default']);

        // ... developer creates a claim ...
        $claimDate = Carbon::now()->startOfSecond();
        Carbon::setTestNow($claimDate);

        $response = $this->actingAs($developer)->postJson(route('achievement-set-claim.create', $game->id));
        $response->assertStatus(302);

        $claim = $game->achievementSetClaims()->first();
        $this->assertNotNull($claim);

        // ... add achievements so the claim can be completed ...
        $this->seedAchievements(amount: 6, game: $game);

        // ... developer completes the claim ...
        $completeDate = $claimDate->clone()->addDays(30);
        Carbon::setTestNow($completeDate);

        Session::flush();
        $response = $this->actingAs($developer)->postJson(route('achievement-set-claim.update', $claim->id), [
            'status' => ClaimStatus::Complete,
        ]);
        $response->assertStatus(302);

        $claim->refresh();
        $this->assertEquals(ClaimStatus::Complete, $claim->status);

        // ... moderator reactivates the claim ...
        $reactivateDate = $completeDate->clone()->addDays(7);
        Carbon::setTestNow($reactivateDate);

        Session::flush();
        $response = $this->actingAs($moderator)->postJson(route('achievement-set-claim.update', $claim->id), [
            'status' => ClaimStatus::Active,
        ]);
        $response->assertStatus(302);

        $claim->refresh();
        $this->assertEquals(ClaimStatus::Active, $claim->status);

        // ... moderator sets the claim back to Complete ...
        $recompleteDate = $reactivateDate->clone()->addDays(1);
        Carbon::setTestNow($recompleteDate);

        Session::flush();
        $response = $this->actingAs($moderator)->postJson(route('achievement-set-claim.update', $claim->id), [
            'status' => ClaimStatus::Complete,
        ]);

        $response->assertStatus(302);
        $response->assertSessionHas('success', 'Claim updated successfully');

        $claim->refresh();
        $this->assertEquals(ClaimStatus::Complete, $claim->status);
        $this->assertEquals($recompleteDate, $claim->updated_at);
    }
}

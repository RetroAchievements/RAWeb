<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserProfileTest extends TestCase
{
    use RefreshDatabase;
    use BootstrapsApiV1;

    public function testItValidates(): void
    {
        $this->get($this->apiUrl('GetUserCompletionProgress'))
            ->assertJsonValidationErrors([
                'u',
            ]);
    }

    public function testGetUserProfileUnknownUser(): void
    {
        $this->get($this->apiUrl('GetUserProfile', ['u' => 'nonExistant']))
            ->assertNotFound()
            ->assertJson([]);
    }

    public function testGetUserProfileUnknownUlid(): void
    {
        $this->get($this->apiUrl('GetUserProfile', ['u' => '01HNG49MXJA71KCVG3PXQS5B2C']))
            ->assertNotFound()
            ->assertJson([]);
    }

    public function testGetUserProfileByUsername(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        $this->get($this->apiUrl('GetUserProfile', ['u' => $user->username]))
            ->assertSuccessful()
            ->assertJson([
                'User' => $user->username,
                'UserPic' => sprintf("/UserPic/%s.png", $user->username),
                'MemberSince' => $user->created_at->toDateTimeString(),
                'RichPresenceMsg' => ($user->rich_presence) ? $user->rich_presence : null,
                'LastGameID' => $user->rich_presence_game_id,
                'ContribCount' => $user->yield_unlocks,
                'ContribYield' => $user->yield_points,
                'TotalPoints' => $user->points_hardcore,
                'TotalSoftcorePoints' => $user->points,
                'TotalTruePoints' => $user->points_weighted,
                'Permissions' => $user->getAttribute('Permissions'),
                'Untracked' => $user->unranked_at !== null,
                'ID' => $user->id,
                'UserWallActive' => $user->is_user_wall_active,
                'Motto' => $user->motto,
            ]);
    }

    public function testGetUserProfileByUlid(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        $this->get($this->apiUrl('GetUserProfile', ['u' => $user->ulid]))
            ->assertSuccessful()
            ->assertJson([
                'User' => $user->username,
                'ULID' => $user->ulid,
                'UserPic' => sprintf("/UserPic/%s.png", $user->username),
                'MemberSince' => $user->created_at->toDateTimeString(),
                'RichPresenceMsg' => ($user->rich_presence) ? $user->rich_presence : null,
                'LastGameID' => $user->rich_presence_game_id,
                'ContribCount' => $user->yield_unlocks,
                'ContribYield' => $user->yield_points,
                'TotalPoints' => $user->points_hardcore,
                'TotalSoftcorePoints' => $user->points,
                'TotalTruePoints' => $user->points_weighted,
                'Permissions' => $user->getAttribute('Permissions'),
                'Untracked' => $user->unranked_at !== null,
                'ID' => $user->id,
                'UserWallActive' => $user->is_user_wall_active,
                'Motto' => $user->motto,
            ]);
    }

    public function testBooleanFieldsReturnIntegersNotBooleans(): void
    {
        /** @var User $trackedUser */
        $trackedUser = User::factory()->create([
            'unranked_at' => null,
            'is_user_wall_active' => true,
        ]);

        $response = $this->get($this->apiUrl('GetUserProfile', ['u' => $trackedUser->username]));
        $response->assertSuccessful();

        $this->assertIsInt($response->json('Untracked'));
        $this->assertSame(0, $response->json('Untracked'));
        $this->assertIsInt($response->json('UserWallActive'));
        $this->assertSame(1, $response->json('UserWallActive'));

        /** @var User $untrackedUser */
        $untrackedUser = User::factory()->create([
            'unranked_at' => now(),
            'is_user_wall_active' => false,
        ]);

        $response = $this->get($this->apiUrl('GetUserProfile', ['u' => $untrackedUser->username]));
        $response->assertSuccessful();

        $this->assertIsInt($response->json('Untracked'));
        $this->assertSame(1, $response->json('Untracked'));
        $this->assertIsInt($response->json('UserWallActive'));
        $this->assertSame(0, $response->json('UserWallActive'));
    }
}

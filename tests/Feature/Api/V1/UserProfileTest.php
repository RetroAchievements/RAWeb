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
        $this->get($this->apiUrl('GetUserProfile'))
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

    public function testGetUserProfile(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        $this->get($this->apiUrl('GetUserProfile', ['u' => $user->User]))
            ->assertSuccessful()
            ->assertJson([
                'User' => $user->User,
                'UserPic' => sprintf("/UserPic/%s.png", $user->User),
                'MemberSince' => $user->created_at->toDateTimeString(),
                'RichPresenceMsg' => ($user->RichPresenceMsg) ? $user->RichPresenceMsg : null,
                'LastGameID' => $user->LastGameID,
                'ContribCount' => $user->ContribCount,
                'ContribYield' => $user->ContribYield,
                'TotalPoints' => $user->RAPoints,
                'TotalSoftcorePoints' => $user->RASoftcorePoints,
                'TotalTruePoints' => $user->TrueRAPoints,
                'Permissions' => $user->getAttribute('Permissions'),
                'Untracked' => $user->Untracked,
                'ID' => $user->ID,
                'UserWallActive' => $user->UserWallActive,
                'Motto' => $user->Motto,
            ]);
    }
}

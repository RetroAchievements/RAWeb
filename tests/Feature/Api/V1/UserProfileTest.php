<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Site\Models\User;
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
                'MemberSince' => $user->Created?->__toString(),
                'RichPresenceMsg' => empty($user->RichPresenceMsg) || $user->RichPresenceMsg === 'Unknown' ? null : $user->RichPresenceMsg,
                'LastGameID' => (int)$user->LastGameID,
                'ContribCount' => (int)$user->ContribCount,
                'ContribYield' => (int)$user->ContribYield,
                'TotalPoints' => (int)$user->RAPoints,
                'TotalSoftcorePoints' => (int)$user->RASoftcorePoints,
                'TotalTruePoints' => (int)$user->TrueRAPoints,
                'Permissions' => (int)$user->getAttribute('Permissions'),
                'Untracked' => (int)$user->Untracked,
                'ID' => (int)$user->ID,
                'UserWallActive' => (int)$user->UserWallActive,
                'Motto' => $user->Motto
            ]);
    }
}

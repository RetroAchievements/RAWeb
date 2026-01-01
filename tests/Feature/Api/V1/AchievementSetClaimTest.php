<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Community\Enums\ClaimSetType;
use App\Community\Enums\ClaimSpecial;
use App\Community\Enums\ClaimStatus;
use App\Community\Enums\ClaimType;
use App\Enums\Permissions;
use App\Models\AchievementSetClaim;
use App\Models\Game;
use App\Models\System;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AchievementSetClaimTest extends TestCase
{
    use RefreshDatabase;
    use BootstrapsApiV1;

    public function testGetActiveClaims(): void
    {
        /** @var System $system */
        $system = System::factory()->create();
        Game::factory()->create(['system_id' => $system->id]);
        AchievementSetClaim::factory()->count(51)->create();

        $response = $this->get($this->apiUrl('GetActiveClaims'))
            ->assertSuccessful();

        $this->assertCount(51, $response->json());
    }

    public function testGetCompletedClaims(): void
    {
        // Freeze time
        Carbon::setTestNow(Carbon::now()->startOfSecond());

        /** @var System $system */
        $system = System::factory()->create();
        /** @var Game $game */
        $game = Game::factory()->create(['system_id' => $system->id]);
        /** @var User $user */
        $user = User::factory()->create(['Permissions' => Permissions::Developer]);
        /** @var AchievementSetClaim $claim */
        $claim = AchievementSetClaim::factory()->create([
            'user_id' => $user->id,
            'game_id' => $game->id,
            'status' => ClaimStatus::Complete,
        ]);

        $this->get($this->apiUrl('GetClaims', ['k' => '1']))
            ->assertSuccessful()
            ->assertJson([
                [
                    'ClaimType' => ClaimType::Primary->toLegacyInteger(),
                    'ConsoleName' => $system->name,
                    'Created' => $claim->created_at->__toString(),
                    'DoneTime' => $claim->finished_at->__toString(),
                    'Extension' => 0,
                    'GameID' => $game->id,
                    'GameIcon' => '/Images/000001.png',
                    'GameTitle' => $game->title,
                    'ID' => $claim->id,
                    'MinutesLeft' => Carbon::now()->diffInMinutes($claim->finished_at),
                    'SetType' => ClaimSetType::NewSet->toLegacyInteger(),
                    'Special' => ClaimSpecial::None->toLegacyInteger(),
                    'Status' => ClaimStatus::Complete->toLegacyInteger(),
                    'Updated' => $claim->updated_at->__toString(),
                    'User' => $user->username,
                    'ULID' => $user->ulid,
                    'UserIsJrDev' => 0,
                ],
            ]);
    }

    public function testGetDroppedClaims(): void
    {
        // Freeze time
        Carbon::setTestNow(Carbon::now()->startOfSecond());

        /** @var System $system */
        $system = System::factory()->create();
        /** @var Game $game */
        $game = Game::factory()->create(['system_id' => $system->id]);
        /** @var User $user */
        $user = User::factory()->create(['Permissions' => Permissions::JuniorDeveloper]);
        /** @var AchievementSetClaim $claim */
        $claim = AchievementSetClaim::factory()->create([
            'user_id' => $user->id,
            'game_id' => $game->id,
            'status' => ClaimStatus::Dropped,
        ]);

        $this->get($this->apiUrl('GetClaims', ['k' => '2']))
            ->assertSuccessful()
            ->assertJson([
                [
                    'ClaimType' => ClaimType::Primary->toLegacyInteger(),
                    'ConsoleName' => $system->name,
                    'Created' => $claim->created_at->__toString(),
                    'DoneTime' => $claim->finished_at->__toString(),
                    'Extension' => 0,
                    'GameID' => $game->id,
                    'GameIcon' => '/Images/000001.png',
                    'GameTitle' => $game->title,
                    'ID' => $claim->id,
                    'MinutesLeft' => Carbon::now()->diffInMinutes($claim->finished_at),
                    'SetType' => ClaimSetType::NewSet->toLegacyInteger(),
                    'Special' => ClaimSpecial::None->toLegacyInteger(),
                    'Status' => ClaimStatus::Dropped->toLegacyInteger(),
                    'Updated' => $claim->updated_at->__toString(),
                    'User' => $user->username,
                    'ULID' => $user->ulid,
                    'UserIsJrDev' => 1,
                ],
            ]);
    }

    public function testGetExpiredClaims(): void
    {
        /** @var System $system */
        $system = System::factory()->create();
        Game::factory()->create(['system_id' => $system->id]);

        AchievementSetClaim::factory()->create();
        AchievementSetClaim::factory()->create([
            'finished_at' => Carbon::now()->subYears(2),
        ]);

        $response = $this->get($this->apiUrl('GetClaims', ['k' => '3']))
            ->assertSuccessful();

        $this->assertCount(1, $response->json());
    }

    public function testGetUserClaims(): void
    {
        // Freeze time
        $now = Carbon::now()->startOfSecond();
        Carbon::setTestNow($now);

        /** @var System $system */
        $system = System::factory()->create();
        /** @var Game $game */
        $game = Game::factory()->create(['system_id' => $system->id]);
        /** @var User $user */
        $user = User::factory()->create(['Permissions' => Permissions::Developer]);
        /** @var AchievementSetClaim $claim */
        $claim = AchievementSetClaim::factory()->create([
            'user_id' => $user->id,
            'game_id' => $game->id,
        ]);

        $this->get($this->apiUrl('GetUserClaims', ['u' => $user->username]))
            ->assertSuccessful()
            ->assertJson([
                [
                    'ClaimType' => ClaimType::Primary->toLegacyInteger(),
                    'ConsoleName' => $system->name,
                    'Created' => $claim->created_at->__toString(),
                    'DoneTime' => $claim->finished_at->__toString(),
                    'Extension' => 0,
                    'GameID' => $game->id,
                    'GameIcon' => '/Images/000001.png',
                    'GameTitle' => $game->title,
                    'ID' => $claim->id,
                    'MinutesLeft' => (int) $now->diffInMinutes($claim->finished_at, true),
                    'SetType' => ClaimSetType::NewSet->toLegacyInteger(),
                    'Special' => ClaimSpecial::None->toLegacyInteger(),
                    'Status' => ClaimStatus::Active->toLegacyInteger(),
                    'Updated' => $claim->updated_at->__toString(),
                    'User' => $user->username,
                    'ULID' => $user->ulid,
                    'UserIsJrDev' => 0,
                ],
            ]);
    }
}

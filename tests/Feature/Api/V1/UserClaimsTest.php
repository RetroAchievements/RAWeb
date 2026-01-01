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

class UserClaimsTest extends TestCase
{
    use RefreshDatabase;
    use BootstrapsApiV1;

    public function testGetUserClaimsUnknownUser(): void
    {
        Game::factory()->create();

        $this->get($this->apiUrl('GetUserClaims', ['u' => 'nonExistant']))
            ->assertNotFound()
            ->assertJson([]);
    }

    public function testGetUserClaimsByName(): void
    {
        // Freeze time
        Carbon::setTestNow(Carbon::now()->startOfSecond());

        /** @var User $user */
        $user = User::factory()->create(['Permissions' => Permissions::Developer]);
        /** @var System $system */
        $system = System::factory()->create();
        /** @var Game $game */
        $game = Game::factory()->create(['system_id' => $system->id]);

        /** @var AchievementSetClaim $claim */
        $claim = AchievementSetClaim::factory()->create([
            'user_id' => $user->id,
            'game_id' => $game->id,
        ]);

        $this->get($this->apiUrl('GetUserClaims', ['u' => $user->username]))
            ->assertSuccessful()
            ->assertJson([
                [
                    'ID' => $claim->id,
                    'User' => $user->username,
                    'ULID' => $user->ulid,
                    'GameID' => $game->id,
                    'GameTitle' => $game->title,
                    'GameIcon' => $game->image_icon_asset_path,
                    'ConsoleName' => $system->name,
                    'ClaimType' => ClaimType::Primary->toLegacyInteger(),
                    'SetType' => ClaimSetType::NewSet->toLegacyInteger(),
                    'Status' => ClaimStatus::Active->toLegacyInteger(),
                    'Extension' => 0,
                    'Special' => ClaimSpecial::None->toLegacyInteger(),
                    'Created' => $claim->created_at->__toString(),
                    'DoneTime' => $claim->finished_at->__toString(),
                    'Updated' => $claim->updated_at->__toString(),
                    'MinutesLeft' => Carbon::now()->diffInMinutes($claim->finished_at),
                ],
            ]);
    }

    public function testGetUserClaimsByUlid(): void
    {
        // Freeze time
        Carbon::setTestNow(Carbon::now()->startOfSecond());

        /** @var User $user */
        $user = User::factory()->create(['Permissions' => Permissions::Developer]);
        /** @var System $system */
        $system = System::factory()->create();
        /** @var Game $game */
        $game = Game::factory()->create(['system_id' => $system->id]);

        /** @var AchievementSetClaim $claim */
        $claim = AchievementSetClaim::factory()->create([
            'user_id' => $user->id,
            'game_id' => $game->id,
        ]);

        $this->get($this->apiUrl('GetUserClaims', ['u' => $user->ulid]))
            ->assertSuccessful()
            ->assertJson([
                [
                    'ID' => $claim->id,
                    'User' => $user->username,
                    'ULID' => $user->ulid,
                    'GameID' => $game->id,
                    'GameTitle' => $game->title,
                    'GameIcon' => $game->image_icon_asset_path,
                    'ConsoleName' => $system->name,
                    'ClaimType' => ClaimType::Primary->toLegacyInteger(),
                    'SetType' => ClaimSetType::NewSet->toLegacyInteger(),
                    'Status' => ClaimStatus::Active->toLegacyInteger(),
                    'Extension' => 0,
                    'Special' => ClaimSpecial::None->toLegacyInteger(),
                    'Created' => $claim->created_at->__toString(),
                    'DoneTime' => $claim->finished_at->__toString(),
                    'Updated' => $claim->updated_at->__toString(),
                    'MinutesLeft' => Carbon::now()->diffInMinutes($claim->finished_at),
                ],
            ]);
    }
}

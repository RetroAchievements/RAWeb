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
        Carbon::setTestNow(Carbon::now());

        /** @var User $user */
        $user = User::factory()->create(['Permissions' => Permissions::Developer]);
        /** @var System $system */
        $system = System::factory()->create();
        /** @var Game $game */
        $game = Game::factory()->create(['ConsoleID' => $system->ID]);

        /** @var AchievementSetClaim $claim */
        $claim = AchievementSetClaim::factory()->create([
            'user_id' => $user->id,
            'game_id' => $game->id,
        ]);

        $this->get($this->apiUrl('GetUserClaims', ['u' => $user->User]))
            ->assertSuccessful()
            ->assertJson([
                [
                    'ID' => $claim->ID,
                    'User' => $user->User,
                    'ULID' => $user->ulid,
                    'GameID' => $game->ID,
                    'GameTitle' => $game->Title,
                    'GameIcon' => $game->ImageIcon,
                    'ConsoleName' => $system->Name,
                    'ClaimType' => ClaimType::Primary,
                    'SetType' => ClaimSetType::NewSet,
                    'Status' => ClaimStatus::Active,
                    'Extension' => 0,
                    'Special' => ClaimSpecial::None,
                    'Created' => $claim->Created->__toString(),
                    'DoneTime' => $claim->Finished->__toString(),
                    'Updated' => $claim->Updated->__toString(),
                    'MinutesLeft' => Carbon::now()->diffInRealMinutes($claim->Finished),
                ],
            ]);
    }

    public function testGetUserClaimsByUlid(): void
    {
        // Freeze time
        Carbon::setTestNow(Carbon::now());

        /** @var User $user */
        $user = User::factory()->create(['Permissions' => Permissions::Developer]);
        /** @var System $system */
        $system = System::factory()->create();
        /** @var Game $game */
        $game = Game::factory()->create(['ConsoleID' => $system->ID]);

        /** @var AchievementSetClaim $claim */
        $claim = AchievementSetClaim::factory()->create([
            'user_id' => $user->id,
            'game_id' => $game->id,
        ]);

        $this->get($this->apiUrl('GetUserClaims', ['u' => $user->ulid]))
            ->assertSuccessful()
            ->assertJson([
                [
                    'ID' => $claim->ID,
                    'User' => $user->User,
                    'ULID' => $user->ulid,
                    'GameID' => $game->ID,
                    'GameTitle' => $game->Title,
                    'GameIcon' => $game->ImageIcon,
                    'ConsoleName' => $system->Name,
                    'ClaimType' => ClaimType::Primary,
                    'SetType' => ClaimSetType::NewSet,
                    'Status' => ClaimStatus::Active,
                    'Extension' => 0,
                    'Special' => ClaimSpecial::None,
                    'Created' => $claim->Created->__toString(),
                    'DoneTime' => $claim->Finished->__toString(),
                    'Updated' => $claim->Updated->__toString(),
                    'MinutesLeft' => Carbon::now()->diffInRealMinutes($claim->Finished),
                ],
            ]);
    }
}

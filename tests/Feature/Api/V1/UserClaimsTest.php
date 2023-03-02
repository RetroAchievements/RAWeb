<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use LegacyApp\Community\Enums\ClaimSetType;
use LegacyApp\Community\Enums\ClaimSpecial;
use LegacyApp\Community\Enums\ClaimStatus;
use LegacyApp\Community\Enums\ClaimType;
use LegacyApp\Community\Models\AchievementSetClaim;
use LegacyApp\Platform\Models\Game;
use LegacyApp\Platform\Models\System;
use LegacyApp\Site\Enums\Permissions;
use LegacyApp\Site\Models\User;
use Tests\TestCase;

class UserClaimsTest extends TestCase
{
    use RefreshDatabase;
    use BootstrapsApiV1;

    public function testGetUserClaimsUnknownUser(): void
    {
        $game = Game::factory()->create();

        $this->get($this->apiUrl('GetUserClaims', ['u' => 'nonExistant']))
            ->assertSuccessful()
            ->assertJson([]);
    }

    public function testGetUserClaims(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        /** @var System $system */
        $system = System::factory()->create();
        /** @var Game $game */
        $game = Game::factory()->create(['ConsoleID' => $system->ID]);

        insertClaim(
            $user->User,
            $game->ID,
            ClaimType::Primary,
            ClaimSetType::NewSet,
            ClaimSpecial::None,
            Permissions::Developer
        );
        $claim = AchievementSetClaim::first();

        $this->get($this->apiUrl('GetUserClaims', ['u' => $user->User]))
            ->assertSuccessful()
            ->assertJson([
                [
                    'ID' => $claim->ID,
                    'User' => $user->User,
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

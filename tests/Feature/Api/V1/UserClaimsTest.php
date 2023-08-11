<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Community\Enums\ClaimSetType;
use App\Community\Enums\ClaimSpecial;
use App\Community\Enums\ClaimStatus;
use App\Community\Enums\ClaimType;
use App\Community\Models\AchievementSetClaim;
use App\Platform\Models\Game;
use App\Platform\Models\System;
use App\Site\Enums\Permissions;
use App\Site\Models\User;
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
            ->assertSuccessful()
            ->assertJson([]);
    }

    public function testGetUserClaims(): void
    {
        // Freeze time
        Carbon::setTestNow(Carbon::now());

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

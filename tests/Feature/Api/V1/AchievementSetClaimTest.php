<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use Illuminate\Foundation\Testing\RefreshDatabase;
use LegacyApp\Community\Enums\ClaimSetType;
use LegacyApp\Community\Enums\ClaimSpecial;
use LegacyApp\Community\Enums\ClaimStatus;
use LegacyApp\Community\Enums\ClaimType;
use LegacyApp\Community\Models\AchievementSetClaim;
use LegacyApp\Platform\Models\Game;
use LegacyApp\Platform\Models\System;
use Tests\TestCase;

class AchievementSetClaimTest extends TestCase
{
    use RefreshDatabase;
    use BootstrapsApiV1;

    public function testGetActiveClaims(): void
    {
        /** @var System $system */
        $system = System::factory()->create();
        /** @var Game $game */
        $game = Game::factory()->create(['ConsoleID' => $system->ID]);
        AchievementSetClaim::factory()->count(100)->create();

        $response = $this->get($this->apiUrl('GetActiveClaims'))
            ->assertSuccessful();

        $this->assertCount(100, $response->json());
    }

    public function testGetUserClaims(): void
    {
        /** @var System $system */
        $system = System::factory()->create();
        /** @var Game $game */
        $game = Game::factory()->create(['ConsoleID' => $system->ID]);
        /** @var AchievementSetClaim $claim */
        $claim = AchievementSetClaim::factory()->create([
            'GameID' => $game->ID,
            'User' => $this->user->User,
        ]);

        $this->get($this->apiUrl('GetUserClaims', ['u' => $this->user->User]))
            ->assertSuccessful()
            ->assertExactJson([
                [
                    'ClaimType' => ClaimType::Primary,
                    'ConsoleName' => $system->Name,
                    'Created' => $claim->Created->__toString(),
                    'DoneTime' => $claim->Finished->__toString(),
                    'Extension' => 0,
                    'GameID' => $game->ID,
                    'GameIcon' => '/Images/000001.png',
                    'GameTitle' => $game->Title,
                    'ID' => $claim->ID,
                    'MinutesLeft' => 0,
                    'SetType' => ClaimSetType::NewSet,
                    'Special' => ClaimSpecial::None,
                    'Status' => ClaimStatus::Active,
                    'Updated' => $claim->Updated->__toString(),
                    'User' => $this->user->User,
                ],
            ]);
    }
}

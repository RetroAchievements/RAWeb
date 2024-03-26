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
        Game::factory()->create(['ConsoleID' => $system->ID]);
        AchievementSetClaim::factory()->count(51)->create();

        $response = $this->get($this->apiUrl('GetActiveClaims'))
            ->assertSuccessful();

        $this->assertCount(51, $response->json());
    }

    public function testGetCompletedClaims(): void
    {
        // Freeze time
        Carbon::setTestNow(Carbon::now());

        /** @var System $system */
        $system = System::factory()->create();
        /** @var Game $game */
        $game = Game::factory()->create(['ConsoleID' => $system->ID]);
        /** @var User $user */
        $user = User::factory()->create(['Permissions' => Permissions::Developer]);

        insertClaim(
            $user,
            $game->ID,
            ClaimType::Primary,
            ClaimSetType::NewSet,
            ClaimSpecial::None,
        );

        completeClaim(
            $user->User,
            $game->ID,
        );

        $claim = AchievementSetClaim::first();

        $this->get($this->apiUrl('GetClaims', ['k' => '1']))
            ->assertSuccessful()
            ->assertJson([
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
                    'MinutesLeft' => Carbon::now()->diffInRealMinutes($claim->Finished),
                    'SetType' => ClaimSetType::NewSet,
                    'Special' => ClaimSpecial::None,
                    'Status' => ClaimStatus::Complete,
                    'Updated' => $claim->Updated->__toString(),
                    'User' => $user->User,
                    'UserIsJrDev' => 0,
                ],
            ]);
    }

    public function testGetDroppedClaims(): void
    {
        // Freeze time
        Carbon::setTestNow(Carbon::now());

        /** @var System $system */
        $system = System::factory()->create();
        /** @var Game $game */
        $game = Game::factory()->create(['ConsoleID' => $system->ID]);
        /** @var User $user */
        $user = User::factory()->create(['Permissions' => Permissions::JuniorDeveloper]);

        insertClaim(
            $user,
            $game->ID,
            ClaimType::Primary,
            ClaimSetType::NewSet,
            ClaimSpecial::None,
        );

        dropClaim(
            $user->User,
            $game->ID,
        );

        $claim = AchievementSetClaim::first();

        $this->get($this->apiUrl('GetClaims', ['k' => '2']))
            ->assertSuccessful()
            ->assertJson([
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
                    'MinutesLeft' => Carbon::now()->diffInRealMinutes($claim->Finished),
                    'SetType' => ClaimSetType::NewSet,
                    'Special' => ClaimSpecial::None,
                    'Status' => ClaimStatus::Dropped,
                    'Updated' => $claim->Updated->__toString(),
                    'User' => $user->User,
                    'UserIsJrDev' => 1,
                ],
            ]);
    }

    public function testGetExpiredClaims(): void
    {
        /** @var System $system */
        $system = System::factory()->create();
        Game::factory()->create(['ConsoleID' => $system->ID]);

        AchievementSetClaim::factory()->create();
        AchievementSetClaim::factory()->create([
            'Finished' => Carbon::now()->subYears(2),
        ]);

        $response = $this->get($this->apiUrl('GetClaims', ['k' => '3']))
            ->assertSuccessful();

        $this->assertCount(1, $response->json());
    }

    public function testGetUserClaims(): void
    {
        // Freeze time
        Carbon::setTestNow(Carbon::now());

        /** @var System $system */
        $system = System::factory()->create();
        /** @var Game $game */
        $game = Game::factory()->create(['ConsoleID' => $system->ID]);
        /** @var User $user */
        $user = User::factory()->create(['Permissions' => Permissions::Developer]);

        insertClaim(
            $user,
            $game->ID,
            ClaimType::Primary,
            ClaimSetType::NewSet,
            ClaimSpecial::None,
        );
        $claim = AchievementSetClaim::first();

        $this->get($this->apiUrl('GetUserClaims', ['u' => $user->User]))
            ->assertSuccessful()
            ->assertJson([
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
                    'MinutesLeft' => Carbon::now()->diffInRealMinutes($claim->Finished),
                    'SetType' => ClaimSetType::NewSet,
                    'Special' => ClaimSpecial::None,
                    'Status' => ClaimStatus::Active,
                    'Updated' => $claim->Updated->__toString(),
                    'User' => $user->User,
                    'UserIsJrDev' => 0,
                ],
            ]);
    }
}

<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Models\Game;
use App\Models\System;
use App\Models\User;
use App\Platform\Enums\UnlockMode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\Feature\Platform\Concerns\TestsPlayerBadges;
use Tests\TestCase;

class GetRecentGameAwardsTest extends TestCase
{
    use RefreshDatabase;
    use BootstrapsApiV1;
    use TestsPlayerBadges;

    public function testItValidates(): void
    {
        $this->get($this->apiUrl('GetRecentGameAwards', ['d' => '3000-01-01']))
            ->assertJsonValidationErrors([
                'd',
            ]);
    }

    public function testGetAllRecentAwards(): void
    {
        Carbon::setTestNow(Carbon::now());

        /** @var User $playerOne */
        $playerOne = User::factory()->create(['User' => 'playerOne']);
        /** @var User $playerTwo */
        $playerTwo = User::factory()->create(['User' => 'playerTwo']);
        /** @var User $playerThree */
        $playerThree = User::factory()->create(['User' => 'playerThree']);
        /** @var User $playerFour */
        $playerFour = User::factory()->create(['User' => 'playerFour']);
        /** @var System $system */
        $system = System::factory()->create(['ID' => 1]);
        /** @var Game $game */
        $game = Game::factory()->create(['ConsoleID' => $system->ID]);

        $this->addGameBeatenAward($playerOne, $game, UnlockMode::Softcore, Carbon::now()->subMinutes(5));
        $this->addGameBeatenAward($playerTwo, $game, UnlockMode::Hardcore, Carbon::now()->subMinutes(10));
        $this->addMasteryBadge($playerThree, $game, UnlockMode::Softcore, Carbon::now()->subMinutes(1));
        $this->addMasteryBadge($playerFour, $game, UnlockMode::Hardcore, Carbon::now()->subMinutes(15));

        $this->get($this->apiUrl('GetRecentGameAwards'))
            ->assertSuccessful()
            ->assertJson([
                'Count' => 4,
                'Total' => 4,
                'Results' => [
                    [
                        'User' => 'playerThree',
                        'ULID' => $playerThree->ulid,
                        'AwardKind' => 'completed',
                        'AwardDate' => Carbon::now()->subMinutes(1)->toIso8601String(),
                        'GameID' => $game->ID,
                        'GameTitle' => $game->Title,
                        'ConsoleID' => $system->ID,
                        'ConsoleName' => $system->Name,
                    ],
                    [
                        'User' => 'playerOne',
                        'ULID' => $playerOne->ulid,
                        'AwardKind' => 'beaten-softcore',
                        'AwardDate' => Carbon::now()->subMinutes(5)->toIso8601String(),
                        'GameID' => $game->ID,
                        'GameTitle' => $game->Title,
                        'ConsoleID' => $system->ID,
                        'ConsoleName' => $system->Name,
                    ],
                    [
                        'User' => 'playerTwo',
                        'ULID' => $playerTwo->ulid,
                        'AwardKind' => 'beaten-hardcore',
                        'AwardDate' => Carbon::now()->subMinutes(10)->toIso8601String(),
                        'GameID' => $game->ID,
                        'GameTitle' => $game->Title,
                        'ConsoleID' => $system->ID,
                        'ConsoleName' => $system->Name,
                    ],
                    [
                        'User' => 'playerFour',
                        'ULID' => $playerFour->ulid,
                        'AwardKind' => 'mastered',
                        'AwardDate' => Carbon::now()->subMinutes(15)->toIso8601String(),
                        'GameID' => $game->ID,
                        'GameTitle' => $game->Title,
                        'ConsoleID' => $system->ID,
                        'ConsoleName' => $system->Name,
                    ],
                ],
            ]);
    }

    public function testGetRecentAwardsByKind(): void
    {
        Carbon::setTestNow(Carbon::now());

        /** @var User $playerOne */
        $playerOne = User::factory()->create(['User' => 'playerOne']);
        /** @var User $playerTwo */
        $playerTwo = User::factory()->create(['User' => 'playerTwo']);
        /** @var User $playerThree */
        $playerThree = User::factory()->create(['User' => 'playerThree']);
        /** @var User $playerFour */
        $playerFour = User::factory()->create(['User' => 'playerFour']);
        /** @var System $system */
        $system = System::factory()->create(['ID' => 1]);
        /** @var Game $game */
        $game = Game::factory()->create(['ConsoleID' => $system->ID]);

        $this->addGameBeatenAward($playerOne, $game, UnlockMode::Softcore, Carbon::now()->subMinutes(5));
        $this->addGameBeatenAward($playerTwo, $game, UnlockMode::Hardcore, Carbon::now()->subMinutes(10));
        $this->addMasteryBadge($playerThree, $game, UnlockMode::Softcore, Carbon::now()->subMinutes(1));
        $this->addMasteryBadge($playerFour, $game, UnlockMode::Hardcore, Carbon::now()->subMinutes(15));

        $this->get($this->apiUrl('GetRecentGameAwards', ['k' => 'completed,beaten-softcore']))
            ->assertSuccessful()
            ->assertJson([
                'Count' => 2,
                'Total' => 2,
                'Results' => [
                    [
                        'User' => 'playerThree',
                        'ULID' => $playerThree->ulid,
                        'AwardKind' => 'completed',
                        'AwardDate' => Carbon::now()->subMinutes(1)->toIso8601String(),
                        'GameID' => $game->ID,
                        'GameTitle' => $game->Title,
                        'ConsoleID' => $system->ID,
                        'ConsoleName' => $system->Name,
                    ],
                    [
                        'User' => 'playerOne',
                        'ULID' => $playerOne->ulid,
                        'AwardKind' => 'beaten-softcore',
                        'AwardDate' => Carbon::now()->subMinutes(5)->toIso8601String(),
                        'GameID' => $game->ID,
                        'GameTitle' => $game->Title,
                        'ConsoleID' => $system->ID,
                        'ConsoleName' => $system->Name,
                    ],
                ],
            ]);
    }

    public function testGetRecentAwardsWithOffset(): void
    {
        Carbon::setTestNow(Carbon::now());

        /** @var User $playerOne */
        $playerOne = User::factory()->create(['User' => 'playerOne']);
        /** @var User $playerTwo */
        $playerTwo = User::factory()->create(['User' => 'playerTwo']);
        /** @var User $playerThree */
        $playerThree = User::factory()->create(['User' => 'playerThree']);
        /** @var User $playerFour */
        $playerFour = User::factory()->create(['User' => 'playerFour']);
        /** @var System $system */
        $system = System::factory()->create(['ID' => 1]);
        /** @var Game $game */
        $game = Game::factory()->create(['ConsoleID' => $system->ID]);

        $this->addGameBeatenAward($playerOne, $game, UnlockMode::Softcore, Carbon::now()->subMinutes(5));
        $this->addGameBeatenAward($playerTwo, $game, UnlockMode::Hardcore, Carbon::now()->subMinutes(10));
        $this->addMasteryBadge($playerThree, $game, UnlockMode::Softcore, Carbon::now()->subMinutes(1));
        $this->addMasteryBadge($playerFour, $game, UnlockMode::Hardcore, Carbon::now()->subMinutes(15));

        $this->get($this->apiUrl('GetRecentGameAwards', ['o' => '3']))
            ->assertSuccessful()
            ->assertJson([
                'Count' => 1,
                'Total' => 4,
                'Results' => [
                    [
                        'User' => 'playerFour',
                        'ULID' => $playerFour->ulid,
                        'AwardKind' => 'mastered',
                        'AwardDate' => Carbon::now()->subMinutes(15)->toIso8601String(),
                        'GameID' => $game->ID,
                        'GameTitle' => $game->Title,
                        'ConsoleID' => $system->ID,
                        'ConsoleName' => $system->Name,
                    ],
                ],
            ]);
    }
}

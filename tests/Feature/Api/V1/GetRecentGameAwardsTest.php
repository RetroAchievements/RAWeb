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
        $playerOne = User::factory()->create(['username' => 'playerOne']);
        /** @var User $playerTwo */
        $playerTwo = User::factory()->create(['username' => 'playerTwo']);
        /** @var User $playerThree */
        $playerThree = User::factory()->create(['username' => 'playerThree']);
        /** @var User $playerFour */
        $playerFour = User::factory()->create(['username' => 'playerFour']);
        /** @var System $system */
        $system = System::factory()->create(['id' => 1]);
        /** @var Game $game */
        $game = Game::factory()->create(['system_id' => $system->id]);

        $this->addGameBeatenAward($playerOne, $game, UnlockMode::Casual, Carbon::now()->subMinutes(5));
        $this->addGameBeatenAward($playerTwo, $game, UnlockMode::Hardcore, Carbon::now()->subMinutes(10));
        $this->addMasteryBadge($playerThree, $game, UnlockMode::Casual, Carbon::now()->subMinutes(1));
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
                        'GameID' => $game->id,
                        'GameTitle' => $game->title,
                        'ConsoleID' => $system->id,
                        'ConsoleName' => $system->name,
                    ],
                    [
                        'User' => 'playerOne',
                        'ULID' => $playerOne->ulid,
                        'AwardKind' => 'beaten-softcore',
                        'AwardDate' => Carbon::now()->subMinutes(5)->toIso8601String(),
                        'GameID' => $game->id,
                        'GameTitle' => $game->title,
                        'ConsoleID' => $system->id,
                        'ConsoleName' => $system->name,
                    ],
                    [
                        'User' => 'playerTwo',
                        'ULID' => $playerTwo->ulid,
                        'AwardKind' => 'beaten-hardcore',
                        'AwardDate' => Carbon::now()->subMinutes(10)->toIso8601String(),
                        'GameID' => $game->id,
                        'GameTitle' => $game->title,
                        'ConsoleID' => $system->id,
                        'ConsoleName' => $system->name,
                    ],
                    [
                        'User' => 'playerFour',
                        'ULID' => $playerFour->ulid,
                        'AwardKind' => 'mastered',
                        'AwardDate' => Carbon::now()->subMinutes(15)->toIso8601String(),
                        'GameID' => $game->id,
                        'GameTitle' => $game->title,
                        'ConsoleID' => $system->id,
                        'ConsoleName' => $system->name,
                    ],
                ],
            ]);
    }

    public function testGetRecentAwardsByKind(): void
    {
        Carbon::setTestNow(Carbon::now());

        /** @var User $playerOne */
        $playerOne = User::factory()->create(['username' => 'playerOne']);
        /** @var User $playerTwo */
        $playerTwo = User::factory()->create(['username' => 'playerTwo']);
        /** @var User $playerThree */
        $playerThree = User::factory()->create(['username' => 'playerThree']);
        /** @var User $playerFour */
        $playerFour = User::factory()->create(['username' => 'playerFour']);
        /** @var System $system */
        $system = System::factory()->create(['id' => 1]);
        /** @var Game $game */
        $game = Game::factory()->create(['system_id' => $system->id]);

        $this->addGameBeatenAward($playerOne, $game, UnlockMode::Casual, Carbon::now()->subMinutes(5));
        $this->addGameBeatenAward($playerTwo, $game, UnlockMode::Hardcore, Carbon::now()->subMinutes(10));
        $this->addMasteryBadge($playerThree, $game, UnlockMode::Casual, Carbon::now()->subMinutes(1));
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
                        'GameID' => $game->id,
                        'GameTitle' => $game->title,
                        'ConsoleID' => $system->id,
                        'ConsoleName' => $system->name,
                    ],
                    [
                        'User' => 'playerOne',
                        'ULID' => $playerOne->ulid,
                        'AwardKind' => 'beaten-softcore',
                        'AwardDate' => Carbon::now()->subMinutes(5)->toIso8601String(),
                        'GameID' => $game->id,
                        'GameTitle' => $game->title,
                        'ConsoleID' => $system->id,
                        'ConsoleName' => $system->name,
                    ],
                ],
            ]);
    }

    public function testGetRecentAwardsFiltersByDate(): void
    {
        Carbon::setTestNow(Carbon::today()->setHour(12));

        /** @var User $playerOne */
        $playerOne = User::factory()->create(['username' => 'playerOne']);
        /** @var User $playerTwo */
        $playerTwo = User::factory()->create(['username' => 'playerTwo']);
        /** @var User $playerThree */
        $playerThree = User::factory()->create(['username' => 'playerThree']);
        /** @var System $system */
        $system = System::factory()->create(['id' => 1]);
        /** @var Game $game */
        $game = Game::factory()->create(['system_id' => $system->id]);

        $earlyToday = Carbon::today()->setHour(2);
        $lateToday = Carbon::today()->setHour(23)->setMinute(59);
        $tomorrowMorning = Carbon::tomorrow()->setHour(8);

        $this->addGameBeatenAward($playerOne, $game, UnlockMode::Hardcore, $earlyToday);
        $this->addMasteryBadge($playerTwo, $game, UnlockMode::Hardcore, $lateToday);
        $this->addGameBeatenAward($playerThree, $game, UnlockMode::Casual, $tomorrowMorning);

        $this->get($this->apiUrl('GetRecentGameAwards', ['d' => Carbon::today()->format('Y-m-d')]))
            ->assertSuccessful()
            ->assertJson([
                'Count' => 2,
                'Total' => 2,
                'Results' => [
                    [
                        'User' => 'playerTwo',
                        'ULID' => $playerTwo->ulid,
                        'AwardKind' => 'mastered',
                        'AwardDate' => $lateToday->toIso8601String(),
                    ],
                    [
                        'User' => 'playerOne',
                        'ULID' => $playerOne->ulid,
                        'AwardKind' => 'beaten-hardcore',
                        'AwardDate' => $earlyToday->toIso8601String(),
                    ],
                ],
            ]);
    }

    public function testGetRecentAwardsExcludesAwardsAfterTargetDate(): void
    {
        Carbon::setTestNow(Carbon::today()->setHour(12));

        /** @var User $playerOne */
        $playerOne = User::factory()->create(['username' => 'playerOne']);
        /** @var User $playerTwo */
        $playerTwo = User::factory()->create(['username' => 'playerTwo']);
        /** @var System $system */
        $system = System::factory()->create(['id' => 1]);
        /** @var Game $game */
        $game = Game::factory()->create(['system_id' => $system->id]);

        $todayAward = Carbon::today()->setHour(10);
        $yesterdayAward = Carbon::yesterday()->setHour(15);

        $this->addMasteryBadge($playerOne, $game, UnlockMode::Hardcore, $todayAward);
        $this->addGameBeatenAward($playerTwo, $game, UnlockMode::Hardcore, $yesterdayAward);

        $this->get($this->apiUrl('GetRecentGameAwards', ['d' => Carbon::yesterday()->format('Y-m-d')]))
            ->assertSuccessful()
            ->assertJson([
                'Count' => 1,
                'Total' => 1,
                'Results' => [
                    [
                        'User' => 'playerTwo',
                        'ULID' => $playerTwo->ulid,
                        'AwardKind' => 'beaten-hardcore',
                        'AwardDate' => $yesterdayAward->toIso8601String(),
                    ],
                ],
            ]);
    }

    public function testGetRecentAwardsWithOffset(): void
    {
        Carbon::setTestNow(Carbon::now());

        /** @var User $playerOne */
        $playerOne = User::factory()->create(['username' => 'playerOne']);
        /** @var User $playerTwo */
        $playerTwo = User::factory()->create(['username' => 'playerTwo']);
        /** @var User $playerThree */
        $playerThree = User::factory()->create(['username' => 'playerThree']);
        /** @var User $playerFour */
        $playerFour = User::factory()->create(['username' => 'playerFour']);
        /** @var System $system */
        $system = System::factory()->create(['id' => 1]);
        /** @var Game $game */
        $game = Game::factory()->create(['system_id' => $system->id]);

        $this->addGameBeatenAward($playerOne, $game, UnlockMode::Casual, Carbon::now()->subMinutes(5));
        $this->addGameBeatenAward($playerTwo, $game, UnlockMode::Hardcore, Carbon::now()->subMinutes(10));
        $this->addMasteryBadge($playerThree, $game, UnlockMode::Casual, Carbon::now()->subMinutes(1));
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
                        'GameID' => $game->id,
                        'GameTitle' => $game->title,
                        'ConsoleID' => $system->id,
                        'ConsoleName' => $system->name,
                    ],
                ],
            ]);
    }
}

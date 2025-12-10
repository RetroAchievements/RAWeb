<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Achievement;
use App\Models\Game;
use App\Models\GameRelease;
use App\Models\Leaderboard;
use App\Models\Role;
use App\Models\System;
use App\Models\User;
use App\Platform\Actions\UpdateGameMetricsAction;
use App\Platform\Enums\ReleasedAtGranularity;
use App\Platform\Enums\AchievementType;
use Carbon\Carbon;
use Faker\Factory as Faker;
use Faker\Generator as FakerGenerator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class GameReleasesSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create();

        foreach (Game::all() as $game) {
            $release = GameRelease::where('game_id', $game->id)->first();
            if (!$release) {
                $release = new GameRelease([
                    'game_id' => $game->id,
                    'title' => $game->title,
                ]);
            }

            $year = 0;
            switch ($game->ConsoleID) {
                case 57: // channel F
                    $range = [1976, 1979];
                    break;
                case 25: // atari 2600
                    $range = [1977, 1992];
                    break;
                case 45: // intellivision
                    $range = [1979, 1990];
                    break;
                case 7: // NES
                    $range = [1983, 1995];
                    break;
                case 1: // MegaDrive
                    $range = [1988, 1997];
                    break;
                case 4: // GameBoy
                    $range = [1989, 2003];
                    break;
                case 15: // GameGear
                    $range = [1990, 1997];
                    break;
                case 3: // SNES
                    $range = [1990, 1999];
                    break;
                case 12: // PSX
                    $range = [1994, 2006];
                    break;
                case 2: // N64
                    $range = [1996, 2002];
                    break;
                case 6: // GameBoy Color
                    $range = [1998, 2003];
                    break;
                case 21: // PS2
                    $range = [2000, 2013];
                    break;
                case 16: // GameCube
                    $range = [2001, 2007];
                    break;
                case 5: // GameBoy Advance
                    $range = [2001, 2010];
                    break;
                case 41: // PSP
                    $range = [2004, 2014];
                    break;
                default:
                    // unspecified, weight towards the newest date
                    $year = (int)sqrt(random_int(1976*1976, 2012*2012));
                    break;
            }
            if ($year === 0) {
                // weight ranged releases towards the initial date
                $year = $range[1] - (int)sqrt(random_int(0, pow($range[1] - $range[0] + 1, 2)));
            }

            $release->released_at = Carbon::createFromDate($year, 1, 1);
            if ($year < 1985 && random_int(0, (1986 - $year) * (1987 - $year)) > 5) {
                $release->released_at_granularity = ReleasedAtGranularity::Year;
            }
            else if ($year < 1988 && random_int(0, (1990 - $year) * (1991 - $year)) > 9) {
                $release->released_at = $release->released_at->addMonths(rand(0, 11));
                $release->released_at_granularity = ReleasedAtGranularity::Month;
            }
            else {
                $release->released_at = $release->released_at->addDays(rand(0, 364));
                $release->released_at_granularity = ReleasedAtGranularity::Day;
            }

            switch (random_int(1, 10)) {
                case 1:
                case 2:
                    $release->region = 'na';
                    break;
                case 3:
                    $release->region = 'eu';
                    break;
                case 4:
                    $release->region = 'jp';
                    break;
                case 5:
                    $release->region = 'worldwide';
                    break;
                case 6: // jp+na
                    $release->region = 'jp';
                    $this->addSecondaryRelease($game->id, $release->released_at, $faker, 'na');
                    break;
                case 7: // jp+eu+na
                    $release->region = 'jp';
                    $this->addSecondaryRelease($game->id, $release->released_at, $faker, 'eu');
                    $this->addSecondaryRelease($game->id, $release->released_at, $faker, 'na');
                    break;
                case 8: // na+eu
                    $release->region = 'na';
                    $this->addSecondaryRelease($game->id, $release->released_at, $faker, 'eu');
                    break;
                case 9:
                    $additionalRegions = ['au', 'ch', 'other'];
                    $release->region = $additionalRegions[array_rand($additionalRegions)];
                    break;
            }

            $release->save();
        }
    }

    private function addSecondaryRelease(int $gameId, Carbon $primaryReleaseDate, FakerGenerator $faker, string $region): void
    {
        GameRelease::create([
            'game_id' => $gameId,
            'title' => ucwords($faker->words(random_int(1, 3), true)),
            'region' => $region,
            'released_at' => $primaryReleaseDate->clone()->addDays(random_int(0, 200)),
            'released_at_granularity' => ReleasedAtGranularity::Day,
            'is_canonical_game_title' => false,
        ]);
    }
}

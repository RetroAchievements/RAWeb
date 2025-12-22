<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Game;
use App\Models\GameSet;
use App\Models\GameSetGame;
use App\Models\GameSetLink;
use App\Platform\Enums\GameSetType;
use Faker\Factory as Faker;
use Illuminate\Database\Seeder;

class GameSetsSeeder extends Seeder
{
    // These hubs are always present in a database and,
    // at the time of writing, appear in the site navbar.
    private const STANDARD_HUBS = [
        '[Central]' => GameSet::CentralHubId,
        '[Central - Genre & Subgenre]' => GameSet::GenreSubgenreHubId,
        '[Central - Series]' => GameSet::SeriesHubId,
        '[Central - Community Events]' => GameSet::CommunityEventsHubId,
        '[Central - Developer Events]' => GameSet::DeveloperEventsHubId,
    ];

    public function run(): void
    {
        $faker = Faker::create();

        foreach (self::STANDARD_HUBS as $title => $id) {
            GameSet::unguard(); // temporarily allow filling the "id" field
            GameSet::create([
                'id' => $id,
                'title' => $title,
                'type' => GameSetType::Hub,
            ]);
            GameSet::reguard();
        }

        $GENRE_HUBS = [
            'Action' => [],
            'Adventure' => [],
            'Arcade' => ['Brick Breakers', 'Bullet Hell', 'Maze Chase'],
            'Educational' => [],
            'Fighting' => ['2D Fighting', '3D Fighting'],
            'Horror' => [],
            'Platforming' => [],
            'Puzzle' => [],
            'Role-Playing Game' => ['Action RPG'],
            'Shooter' => ['First-Person Shooter'],
            'Simulation' => [],
            'Sports' => ['Sports - Basketball', 'Sports - Football | Soccer'],
            'Strategy' => [],
        ];
        $genreHubIds = [];
        foreach ($GENRE_HUBS as $title => $subGenres) {
            $genre = GameSet::create(['title' => "[Genre - $title]", 'type' => GameSetType::Hub]);
            GameSetLink::create([
                'parent_game_set_id' => GameSet::GenreSubgenreHubId,
                'child_game_set_id' => $genre->id,
            ]);
            GameSetLink::create([
                'parent_game_set_id' => $genre->id,
                'child_game_set_id' => GameSet::GenreSubgenreHubId,
            ]);
            $genreHubIds[] = [$genre->id, $title];

            foreach ($subGenres as $subGenreTitle) {
                $subGenre = GameSet::create(['title' => "[Subgenre - $subGenreTitle]", 'type' => GameSetType::Hub]);
                // associate to parent
                GameSetLink::create([
                    'parent_game_set_id' => $genre->id,
                    'child_game_set_id' => $subGenre->id,
                ]);
                GameSetLink::create([
                    'parent_game_set_id' => $subGenre->id,
                    'child_game_set_id' => $genre->id,
                ]);

                // associate to central hub
                GameSetLink::create([
                    'parent_game_set_id' => GameSet::GenreSubgenreHubId,
                    'child_game_set_id' => $subGenre->id,
                ]);
                GameSetLink::create([
                    'parent_game_set_id' => $subGenre->id,
                    'child_game_set_id' => GameSet::GenreSubgenreHubId,
                ]);

                $genreHubIds[] = [$subGenre->id, $subGenreTitle];
            }
        }

        $developerHubIds = [];
        $developerHub = GameSet::create(['title' => "[Central - Developer]", 'type' => GameSetType::Hub]);
        $num_to_create = sqrt(Game::count()) + random_int(0, 10) + random_int(0, 2) + random_int(0, 2) + random_int(0, 2);
        for ($i = 0; $i < $num_to_create; $i++) {
            $developerName = ucwords($faker->words(random_int(1, 3), true));
            $genre = GameSet::create(['title' => "[Developer - $developerName]", 'type' => GameSetType::Hub]);
            GameSetLink::create([
                'parent_game_set_id' => $developerHub->id,
                'child_game_set_id' => $genre->id,
            ]);
            GameSetLink::create([
                'parent_game_set_id' => $genre->id,
                'child_game_set_id' => $developerHub->id,
            ]);
            $developerHubIds[] = [$genre->id, $developerName];
        }

        $publisherHubIds = [];
        $publisherHub = GameSet::create(['title' => "[Central - Publisher]", 'type' => GameSetType::Hub]);
        $num_to_create = sqrt(Game::count()) / 2 + random_int(0, 5) + random_int(0, 2) + random_int(0, 2);
        for ($i = 0; $i < $num_to_create; $i++) {
            $publisherName = ucwords($faker->words(random_int(1, 3), true));
            $genre = GameSet::create(['title' => "[Publisher - $publisherName]", 'type' => GameSetType::Hub]);
            GameSetLink::create([
                'parent_game_set_id' => $publisherHub->id,
                'child_game_set_id' => $genre->id,
            ]);
            GameSetLink::create([
                'parent_game_set_id' => $genre->id,
                'child_game_set_id' => $publisherHub->id,
            ]);
            $publisherHubIds[] = [$genre->id, $publisherName];
        }

        $publisherCount = count($publisherHubIds);
        $developerCount = count($developerHubIds);
        $genreCount = count($genreHubIds);
        foreach (Game::all() as $game) {
            $index = rand(0, $developerCount + 10);
            if ($index < $developerCount) {
                GameSetGame::create([
                    'game_set_id' => $developerHubIds[$index][0],
                    'game_id' => $game->ID,
                ]);
                $game->Developer = $developerHubIds[$index][1];
            } elseif ($index > $developerCount) {
                $game->Developer = ucwords($faker->words(random_int(1, 3), true));
            }

            $index = rand(0, $publisherCount + 10);
            if ($index < $publisherCount) {
                GameSetGame::create([
                    'game_set_id' => $publisherHubIds[$index][0],
                    'game_id' => $game->ID,
                ]);
                $game->Publisher = $publisherHubIds[$index][1];
            } elseif ($index > $publisherCount) {
                $game->Publisher = ucwords($faker->words(random_int(1, 3), true));
            }

            $index = rand(0, $genreCount);
            if ($index != $genreCount) {
                GameSetGame::create([
                    'game_set_id' => $genreHubIds[$index][0],
                    'game_id' => $game->ID,
                ]);
                $game->Genre = $genreHubIds[$index][1];
            }

            $game->saveQuietly();

            if (str_ends_with($game->Title, " II")) {
                $seriesTitle = substr($game->Title, 0, strlen($game->Title) - 3);
                $genre = GameSet::create(['title' => "[Series - $seriesTitle]", 'type' => GameSetType::Hub]);
                GameSetLink::create([
                    'parent_game_set_id' => GameSet::SeriesHubId,
                    'child_game_set_id' => $genre->id,
                ]);
                GameSetLink::create([
                    'parent_game_set_id' => $genre->id,
                    'child_game_set_id' => GameSet::SeriesHubId,
                ]);

                foreach (Game::where('Title', 'LIKE', "$seriesTitle %")->orWhere('Title', $seriesTitle)->pluck('ID')->toArray() as $seriesGameId) {
                    GameSetGame::create([
                        'game_set_id' => $genre->id,
                        'game_id' => $seriesGameId,
                    ]);
                }
            }
        }

    }
}

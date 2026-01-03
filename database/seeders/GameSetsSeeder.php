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
        foreach (Game::orderBy('released_at')->get() as $game) {
            $index = rand(0, $developerCount + 10);
            if ($index < $developerCount) {
                GameSetGame::create([
                    'game_set_id' => $developerHubIds[$index][0],
                    'game_id' => $game->id,
                ]);
                $game->developer = $developerHubIds[$index][1];
            } elseif ($index > $developerCount) {
                $game->developer = ucwords($faker->words(random_int(1, 3), true));
            }

            $index = rand(0, $publisherCount + 10);
            if ($index < $publisherCount) {
                GameSetGame::create([
                    'game_set_id' => $publisherHubIds[$index][0],
                    'game_id' => $game->id,
                ]);
                $game->publisher = $publisherHubIds[$index][1];
            } elseif ($index > $publisherCount) {
                $game->publisher = ucwords($faker->words(random_int(1, 3), true));
            }

            $index = rand(0, $genreCount);
            if ($index != $genreCount) {
                GameSetGame::create([
                    'game_set_id' => $genreHubIds[$index][0],
                    'game_id' => $game->id,
                ]);
                $game->genre = $genreHubIds[$index][1];
            }

            $game->title = $this->generateTitle($game);
            $game->save();
        }
    }

    private function generateTitle(Game $game): string
    {
        if (rand(1, 10) === 1) {
            // 10% chance of creating a sequel
            $title = $this->generateSequelTitle($game);
            if ($title) {
                return $title;
            }
        }

        $genre = $game->genre ?? '';

        $parts = [];
        $adjectives = [
            'Amazing',
            'Crooked',
            'Dark',
            'Demon',
            'Golden',
            'Haunted',
            'Hidden',
            'Ice',
            'Immortal',
            'Lost',
            'Magic',
            'Strange',
            'Super',
            'The Last',
            'Twisted',
            'Ultimate',
        ];

        $bodies = [
            'Cave',
            'Castle',
            'Tower',
            'Star',
            'Dungeon',
            'Forest',
            'Sword',
            'Crystal',
        ];

        $suffixes = [
            'Legend',
            'of Darkness',
            'Quest',
            'Saga',
            'Slayer',
            'Story',
            'Tale',
            'Warrior',
            'World',
        ];

        if ($genre === 'Simulation') {
            $suffixes = [
                'Builder',
                'Racer',
                'World',
            ];
        }

        if (str_starts_with($genre, 'Sports')) {
            $adjectives = [
                'Extreme',
                'Super',
                'Ultimate',
            ];

            if (str_contains($genre, 'Basketball')) {
                $bodies = ['Basketball'];
            } elseif (str_contains($genre, 'Soccer')) {
                $bodies = ['Soccer'];
            } else {
                $bodies = [
                    'Baseball',
                    'Golf',
                    'Hockey',
                    'Tennis',
                    'Track',
                    'Volleyball',
                    'Wrestling',
                ];
            }

            $suffixes = [
                'Clash',
                'Jam',
                'Showdown',
                'Story',
            ];
        } elseif (rand(0, 7) === 0) {
            // 12% chance of prefixing 'The'
            $parts[] = 'The';
        }

        if (rand(0, 3) === 0) {
            // 25% chance of random name for title
            $faker = Faker::create();
            $word = $faker->word();
            while (strlen($word) < 4) {
                $word = $faker->word();
            }
            $bodies = [ucfirst($word)];

            if (rand(0, 1) === 0) {
                // 50% chance of not having an adjective
                $adjectives = [];
            }

            if (rand(0, 2) !== 2) {
                // 66% chance of not having a suffix
                $suffixes = [];
            }
        }

        if ($genre === 'Adventure' || $genre === 'Role-Playing Game' || $genre === 'Action RPG') {
            $prefixes = ['The Legend of', 'King of'];
            $prefixIndex = rand(0, 10);
            if ($prefixIndex < count($prefixes)) {
                $parts[] = $prefixes[$prefixIndex];
                $suffixes = []; // prefix trumps suffix
            }
        }

        $adjectiveIndex = rand(0, count($adjectives) + 2);
        if ($adjectiveIndex < count($adjectives)) {
            $parts[] = $adjectives[$adjectiveIndex];
        }

        $bodyIndex = rand(0, count($bodies) - 1);
        $parts[] = $bodies[$bodyIndex];

        $suffixIndex = rand(0, count($suffixes) + 3);
        if ($suffixIndex < count($suffixes)) {
            $parts[] = $suffixes[$suffixIndex];
        }

        $newTitle = str_replace('The The', 'The', implode(' ', $parts));

        $otherGame = Game::firstWhere('title', $newTitle);
        if ($otherGame && $otherGame->system_id === $game->system_id) {
            // game already exists with this title on this system, try again.
            return $this->generateTitle($game);
        }

        return $newTitle;
    }

    private function generateSequelTitle(Game $game): ?string
    {
        $priorGame = Game::where('genre', $game->genre)->inRandomOrder()->first();
        if (!$priorGame) {
            return null;
        }

        $sequelSuffixes = [
            'II',
            'III',
            'IV',
            'V',
            'VI',
            'VII',
            'VIII',
            'IX',
            'X',

            '2',
            '3',
            '4',
            '5',
            '6',
            '7',
            '8',
            '9',
        ];

        $sequelSuffixIndex = 0;
        if (rand(0, 1) === 0) {
            $sequelSuffixIndex = array_search('2', $sequelSuffixes);
        }

        $title = $priorGame->title;
        $space = strrpos($priorGame->title, ' ');
        if ($space) {
            $lastWord = substr($priorGame->title, $space + 1);
            $index = array_search($lastWord, $sequelSuffixes);
            if ($index !== false) {
                $title = substr($priorGame->title, 0, $space);
                $sequelSuffixIndex = $index + 1;
            }
        }

        do {
            $newTitle = $title . ' ' . $sequelSuffixes[$sequelSuffixIndex];
            if (!Game::where('title', $newTitle)->exists()) {
                $series = GameSet::firstWhere('title', "[Series - $title]");
                if (!$series) {
                    $series = GameSet::create(['title' => "[Series - $title]", 'type' => GameSetType::Hub]);

                    GameSetLink::create([
                        'parent_game_set_id' => GameSet::SeriesHubId,
                        'child_game_set_id' => $series->id,
                    ]);
                    GameSetLink::create([
                        'parent_game_set_id' => $series->id,
                        'child_game_set_id' => GameSet::SeriesHubId,
                    ]);

                    GameSetGame::create([
                        'game_set_id' => $series->id,
                        'game_id' => $priorGame->id,
                    ]);
                }

                GameSetGame::create([
                    'game_set_id' => $series->id,
                    'game_id' => $game->id,
                ]);

                return $newTitle;
            }

            $sequelSuffixIndex++;
        } while ($sequelSuffixIndex < count($sequelSuffixes));

        return null;
    }
}

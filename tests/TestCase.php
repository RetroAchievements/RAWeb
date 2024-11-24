<?php

declare(strict_types=1);

namespace Tests;

use App\Models\Achievement;
use App\Models\Game;
use App\Models\GameHash;
use App\Models\System;
use App\Models\User;
use Database\Seeders\Concerns\SeedsUsers;
use Database\Seeders\RolesTableSeeder;
use Database\Seeders\UsersTableSeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Collection;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;
    use SeedsUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    protected function assertPathEquals(string $expected, string $actual, string $message = ''): void
    {
        $this->assertEquals(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $expected), $actual, $message);
    }

    protected function seedUsers(): void
    {
        $this->seed(RolesTableSeeder::class);
        $this->seed(UsersTableSeeder::class);
    }

    protected function seedUser(string $username = 'verified'): User
    {
        $this->seed(RolesTableSeeder::class);

        return $this->seedUserByUsername($username);
    }

    protected function seedSystem(int $gamesAmount = 0): System
    {
        /** @var System $system */
        $system = System::factory()->create();

        if ($gamesAmount > 0) {
            $this->seedGames($gamesAmount, $system);
        }

        return $system;
    }

    /**
     * @return Collection<int, Game>
     */
    protected function seedGames(int $amount = 3, ?System $system = null, int $achievementsAmount = 0, bool $withHash = true): Collection
    {
        if ($system === null) {
            $system = System::factory()->create();
        }

        /** @var System $system */
        /** @var Collection<int, Game> $games */
        $games = $system->games()->saveMany(Game::factory()->count($amount)->create());

        if ($withHash) {
            $games->each(function (Game $game): bool {
                GameHash::create([
                    'game_id' => $game->id,
                    'system_id' => $game->system_id,
                    'md5' => fake()->md5,
                    'name' => 'hash_' . $game->id,
                    'description' => 'hash_' . $game->id,
                ]);

                return true;
            });
        }

        if ($achievementsAmount > 0) {
            $games->each(fn (Game $game) => (bool) $this->seedAchievements($achievementsAmount, $game));
        }

        return $games;
    }

    protected function seedGame(?System $system = null, int $achievements = 0, bool $withHash = true): Game
    {
        return $this->seedGames(1, $system, $achievements, $withHash)->first();
    }

    /**
     * @return Collection<int, Achievement>
     */
    protected function seedAchievements(int $amount = 3, ?Game $game = null): Collection
    {
        if ($game === null) {
            $game = $this->seedGame();
        }

        /** @var Collection<int, Achievement> $achievements */
        $achievements = $game->achievements()->saveMany(Achievement::factory()->published()->count($amount)->create());

        $game->achievements_published += $amount;
        $game->save();

        return $achievements;
    }

    protected function seedAchievement(?Game $game = null): Achievement
    {
        return $this->seedAchievements(1, $game)->first();
    }
}

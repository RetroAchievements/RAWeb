<?php

declare(strict_types=1);

namespace Tests;

use App\Platform\Actions\LinkHashToGameAction;
use App\Platform\Models\Achievement;
use App\Platform\Models\Game;
use App\Platform\Models\System;
use App\Site\Models\User;
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

        $this->withoutMix();
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

    protected function seedGames(int $amount = 3, ?System $system = null, int $achievementsAmount = 0, bool $withHash = true): Collection
    {
        if ($system === null) {
            $system = System::factory()->create();
        }

        /** @var System $system */
        /** @var Collection $games */
        $games = $system->games()->saveMany(Game::factory()->count($amount)->create());

        if ($withHash) {
            $games->each(fn (Game $game) => (bool) (new LinkHashToGameAction())->execute($game->id . '_hash', $game));
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

    protected function seedAchievements(int $amount = 3, ?Game $game = null): Collection
    {
        if ($game === null) {
            $game = $this->seedGame();
        }

        /** @var Collection $achievements */
        $achievements = $game->achievements()->saveMany(Achievement::factory()->count($amount)->create());

        return $achievements;
    }

    protected function seedAchievement(?Game $game = null): Achievement
    {
        return $this->seedAchievements(1, $game)->first();
    }
}

<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\GameSet;
use App\Models\GameSetLink;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GameSetLink>
 */
class GameSetLinkFactory extends Factory
{
    protected $model = GameSetLink::class;

    public function definition(): array
    {
        return [
            'parent_game_set_id' => GameSet::factory(),
            'child_game_set_id' => GameSet::factory(),
        ];
    }
}

<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Platform\Models\GameHash;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GameHash>
 */
class GameHashFactory extends Factory
{
    protected $model = GameHash::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'system_id' => 1,
            'hash' => fake()->md5,
        ];
    }
}

<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ModerationWatchlistTerm;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ModerationWatchlistTerm>
 */
class ModerationWatchlistTermFactory extends Factory
{
    protected $model = ModerationWatchlistTerm::class;

    public function definition(): array
    {
        return [
            'term' => $this->faker->unique()->lexify('watched????tool'),
            'note' => null,
        ];
    }
}

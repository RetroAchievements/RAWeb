<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Event;
use App\Models\Game;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<Event>
 */
class EventFactory extends Factory
{
    protected $model = Event::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $activeFrom = Carbon::instance($this->faker->dateTimeBetween('-1 year', '+1 year'));

        return [
            'legacy_game_id' => Game::factory(),
            'slug' => $this->faker->unique()->slug(),
            'active_from' => $activeFrom,
            'active_until' => $activeFrom->clone()->addYear(),
        ];
    }
}

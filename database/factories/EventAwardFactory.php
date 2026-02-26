<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Event;
use App\Models\EventAward;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EventAward>
 */
class EventAwardFactory extends Factory
{
    protected $model = EventAward::class;

    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'tier_index' => 0,
            'label' => ucfirst($this->faker->word()),
            'points_required' => $this->faker->numberBetween(5, 100),
            'image_asset_path' => '/Images/' . $this->faker->numberBetween(0, 99999) . '.png',
        ];
    }
}

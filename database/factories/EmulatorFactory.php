<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Emulator;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Emulator>
 */
class EmulatorFactory extends Factory
{
    protected $model = Emulator::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->word(),
            'original_name' => fake()->unique()->word(),
            'description' => fake()->sentence(),
            'documentation_url' => fake()->url(),
            'download_url' => fake()->url(),
            'download_x64_url' => fake()->url(),
            'source_url' => fake()->url(),
            'order_column' => fake()->numberBetween(1, 100),
            'active' => true,
        ];
    }
}

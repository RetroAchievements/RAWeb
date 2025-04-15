<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Platform;
use App\Platform\Enums\PlatformExecutionEnvironment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Platform>
 */
class PlatformFactory extends Factory
{
    protected $model = Platform::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->word(),
            'execution_environment' => PlatformExecutionEnvironment::Desktop,
            'order_column' => 0,
        ];
    }
}

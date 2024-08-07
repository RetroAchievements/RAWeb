<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\System;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<System>
 */
class SystemFactory extends Factory
{
    protected $model = System::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = ucwords(fake()->word());
        $manufacturer = ucwords(fake()->word());

        return [
            'Name' => $name,
            'name_short' => strtoupper(substr($name, 0, 3)),
            'name_full' => "$manufacturer $name",
            'manufacturer' => $manufacturer,
            'active' => true,
        ];
    }
}

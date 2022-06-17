<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class GameHashFactory extends Factory
{
    public function definition()
    {
        return [
            'system_id' => 1,
            'hash' => $this->faker->md5,
        ];
    }
}

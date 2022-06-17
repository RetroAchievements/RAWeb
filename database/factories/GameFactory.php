<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class GameFactory extends Factory
{
    public function definition()
    {
        return [
            'title' => ucwords($this->faker->words(2, true)),
        ];
    }
}

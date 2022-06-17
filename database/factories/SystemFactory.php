<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class SystemFactory extends Factory
{
    public function definition()
    {
        return [
            'name' => ucwords($this->faker->words(1, true)),
        ];
    }
}

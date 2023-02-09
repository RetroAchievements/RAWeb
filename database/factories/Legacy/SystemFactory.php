<?php

declare(strict_types=1);

namespace Database\Factories\Legacy;

use Illuminate\Database\Eloquent\Factories\Factory;
use LegacyApp\Platform\Models\System;

class SystemFactory extends Factory
{
    protected $model = System::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'Name' => ucwords($this->faker->words(1, true)),
        ];
    }
}

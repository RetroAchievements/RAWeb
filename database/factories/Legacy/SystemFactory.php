<?php

namespace Database\Factories\Legacy;

use Illuminate\Database\Eloquent\Factories\Factory;
use LegacyApp\Platform\Models\System;

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
        return [
            'Name' => ucwords(fake()->words(1, true)),
        ];
    }
}

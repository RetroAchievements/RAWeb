<?php

namespace Database\Factories;

use Database\Seeders\Concerns\SeedsUsers;
use Illuminate\Database\Eloquent\Factories\Factory;

class NewsFactory extends Factory
{
    use SeedsUsers;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => ucwords(fake()->words(2, true)),
            'user_id' => $this->seedUserByUsername(fake()->userName)->id,
            // 'link' => mt_rand(0, 1) ? $faker->url : null,
            'lead' => fake()->text(200),
            'body' => fake()->text,
        ];
    }
}

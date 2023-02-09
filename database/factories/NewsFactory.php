<?php

declare(strict_types=1);

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
            'title' => ucwords($this->faker->words(2, true)),
            'user_id' => $this->seedUserByUsername($this->faker->userName)->id,
            // 'link' => mt_rand(0, 1) ? $faker->url : null,
            'lead' => $this->faker->text(200),
            'body' => $this->faker->text,
        ];
    }
}

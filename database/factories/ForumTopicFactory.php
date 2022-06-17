<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Site\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ForumTopicFactory extends Factory
{
    public function definition()
    {
        $user = User::inRandomOrder()->first();

        return [
            'title' => ucwords($this->faker->words(2, true)),
            'user_id' => $user->id,
        ];
    }
}

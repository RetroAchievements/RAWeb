<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Comment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Comment>
 */
class CommentFactory extends Factory
{
    protected $model = Comment::class;

    public function definition(): array
    {
        $user = User::inRandomOrder()->first();

        return [
            'Payload' => $this->faker->paragraph,
            'Submitted' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'Edited' => $this->faker->dateTimeBetween('now', '+1 year'),
            'user_id' => $user->ID,
            'ArticleID' => $this->faker->numberBetween(1, 100),
            'ArticleType' => $this->faker->numberBetween(1, 3),
        ];
    }
}

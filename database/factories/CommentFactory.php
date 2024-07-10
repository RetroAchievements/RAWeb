<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Comment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Comment>
 */
class CommentFactory extends Factory
{
    protected $model = Comment::class;

    public function definition(): array
    {
        return [
            'Payload' => $this->faker->paragraph,
            'Submitted' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'Edited' => $this->faker->dateTimeBetween('now', '+1 year'),
            'user_id' => 1,
            'commentable_id' => $this->faker->numberBetween(1, 100),
            'commentable_type' => $this->faker->randomElement([
                'App\Models\Article',
                'App\Models\Post',
                'App\Models\Product',
            ]),
        ];
    }
}

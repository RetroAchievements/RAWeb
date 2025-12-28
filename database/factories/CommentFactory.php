<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Community\Enums\CommentableType;
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
        $isEdited = $this->faker->boolean((1 / 12) * 100); // A one-in-twelve chance of being truthy.

        return [
            'body' => $this->faker->paragraph,
            'created_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'updated_at' => $isEdited ? $this->faker->dateTimeBetween('now', '+1 year') : null,
            'user_id' => $user->ID,
            'commentable_id' => $this->faker->numberBetween(1, 100),
            'commentable_type' => $this->faker->randomElement([
                CommentableType::Game,
                CommentableType::Achievement,
                CommentableType::User,
            ]),
        ];
    }
}

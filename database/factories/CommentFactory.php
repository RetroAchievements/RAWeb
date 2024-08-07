<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Community\Enums\ArticleType;
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
            'ArticleType' => ArticleType::Achievement,
            'ArticleID' => 1,
        ];
    }
}

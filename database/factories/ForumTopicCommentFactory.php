<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Community\Models\ForumTopicComment;
use App\Site\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ForumTopicComment>
 */
class ForumTopicCommentFactory extends Factory
{
    protected $model = ForumTopicComment::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $user = User::inRandomOrder()->first();

        return [
            'title' => ucwords(fake()->words(2, true)),
            'user_id' => $user->id,
        ];
    }
}

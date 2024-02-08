<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ForumTopicComment;
use App\Models\User;
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
            'Payload' => ucwords(fake()->words(2, true)),
            'user_id' => $user->ID,
        ];
    }
}

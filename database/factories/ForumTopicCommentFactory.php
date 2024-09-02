<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ForumTopicComment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

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
            'author_id' => $user->ID,
            'authorized_at' => Carbon::now(),
            'Authorised' => 1,
        ];
    }
}

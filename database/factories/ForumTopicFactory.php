<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Forum;
use App\Models\ForumTopic;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ForumTopic>
 */
class ForumTopicFactory extends Factory
{
    protected $model = ForumTopic::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $user = User::inRandomOrder()->first();

        return [
            'title' => ucwords(fake()->words(2, true)),
            'author_id' => $user->id,
            'forum_id' => Forum::factory(),
        ];
    }
}

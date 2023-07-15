<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Community\Models\ForumTopic;
use App\Site\Models\User;
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
            'Title' => ucwords(fake()->words(2, true)),
            'AuthorID' => $user->ID,
        ];
    }
}

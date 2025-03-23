<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Forum;
use App\Models\ForumCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Forum>
 */
class ForumFactory extends Factory
{
    protected $model = Forum::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => ucwords(fake()->words(2, true)),
            'description' => fake()->words(10, true),
            'forum_category_id' => ForumCategory::factory(),
        ];
    }
}

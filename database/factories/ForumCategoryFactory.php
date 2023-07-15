<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Community\Models\ForumCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ForumCategory>
 */
class ForumCategoryFactory extends Factory
{
    protected $model = ForumCategory::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'Name' => ucwords(fake()->words(2, true)),
        ];
    }
}

<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Community\Models\Forum;
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
            'name' => ucwords(fake()->words(2, true)),
        ];
    }
}

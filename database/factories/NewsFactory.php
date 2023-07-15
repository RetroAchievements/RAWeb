<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Community\Models\News;
use Database\Seeders\Concerns\SeedsUsers;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<News>
 */
class NewsFactory extends Factory
{
    use SeedsUsers;

    protected $model = News::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'Title' => ucwords(fake()->words(2, true)),
            'user_id' => $this->seedUserByUsername(fake()->userName)->id,
            // 'link' => mt_rand(0, 1) ? $faker->url : null,
            'lead' => fake()->text(200),
            'Payload' => fake()->text,
        ];
    }
}

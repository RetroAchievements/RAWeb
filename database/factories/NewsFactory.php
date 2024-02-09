<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\News;
use App\Models\User;
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
        $author = User::inRandomOrder()->first();

        return [
            'Title' => ucwords(fake()->words(2, true)),
            'user_id' => $author->ID,
            'Author' => $author->username,
            // 'link' => mt_rand(0, 1) ? $faker->url : null,
            'lead' => fake()->text(200),
            'Payload' => fake()->text,
        ];
    }
}

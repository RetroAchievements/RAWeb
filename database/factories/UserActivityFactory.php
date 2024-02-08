<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Community\Enums\UserActivityType;
use App\Models\UserActivity;
use App\Support\Database\Eloquent\Concerns\FakesUsername;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserActivity>
 */
class UserActivityFactory extends Factory
{
    use FakesUsername;

    protected $model = UserActivity::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'type' => UserActivityType::Login,
            'user_id' => 1,
        ];
    }
}

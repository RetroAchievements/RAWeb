<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Community\Enums\UserRelationStatus;
use App\Models\User;
use App\Models\UserRelation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserRelation>
 */
class UserRelationFactory extends Factory
{
    protected $model = UserRelation::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'related_user_id' => User::factory(),
            'status' => fake()->randomElement(UserRelationStatus::cases()),
        ];
    }

    public function following(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => UserRelationStatus::Following,
            ];
        });
    }

    public function notFollowing(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => UserRelationStatus::NotFollowing,
            ];
        });
    }

    public function blocked(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => UserRelationStatus::Blocked,
            ];
        });
    }
}

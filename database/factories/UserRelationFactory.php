<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Community\Enums\UserRelationship;
use App\Models\User;
use App\Models\UserRelation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserRelation>
 */
class UserRelationFactory extends Factory
{
    protected $model = UserRelation::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'related_user_id' => User::factory(),
            'Friendship' => fake()->randomElement(UserRelationship::cases()),
            'Created' => now(),
            'Updated' => now(),
        ];
    }

    public function following(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'Friendship' => UserRelationship::Following,
            ];
        });
    }

    public function notFollowing(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'Friendship' => UserRelationship::NotFollowing,
            ];
        });
    }

    public function blocked(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'Friendship' => UserRelationship::Blocked,
            ];
        });
    }
}

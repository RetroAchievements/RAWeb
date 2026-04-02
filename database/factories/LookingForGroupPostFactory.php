<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Community\Enums\LookingForGroupStatus;
use App\Models\Game;
use App\Models\LookingForGroupPost;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LookingForGroupPost>
 */
class LookingForGroupPostFactory extends Factory
{
    protected $model = LookingForGroupPost::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'game_id' => Game::factory(),
            'creator_user_id' => User::factory(),
            'title' => $this->faker->sentence(4),
            'note' => $this->faker->optional()->paragraph(),
            'max_players' => $this->faker->optional()->numberBetween(2, 8),
            'scheduled_for' => $this->faker->optional()->dateTimeBetween('+1 day', '+7 days'),
            'status' => LookingForGroupStatus::Active,
            'expires_at' => now()->addDays(30),
        ];
    }

    /**
     * Create a filled post.
     */
    public function filled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => LookingForGroupStatus::Filled,
        ]);
    }

    /**
     * Create a cancelled post.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => LookingForGroupStatus::Cancelled,
        ]);
    }

    /**
     * Create an expired post.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => LookingForGroupStatus::Expired,
            'expires_at' => now()->subDay(),
        ]);
    }

    /**
     * Create a post that expires soon.
     */
    public function expiresSoon(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => now()->addHours(2),
        ]);
    }

    /**
     * Create a post scheduled for today.
     */
    public function scheduledToday(): static
    {
        return $this->state(fn (array $attributes) => [
            'scheduled_for' => now()->addHours($this->faker->numberBetween(1, 12)),
        ]);
    }

    /**
     * Create a post with unlimited players.
     */
    public function unlimitedPlayers(): static
    {
        return $this->state(fn (array $attributes) => [
            'max_players' => null,
        ]);
    }

    /**
     * Create a post for a specific game.
     */
    public function for(Game $game): static
    {
        return $this->state(fn (array $attributes) => [
            'game_id' => $game->id,
        ]);
    }

    /**
     * Create a post by a specific creator.
     */
    public function createdBy(User $creator): static
    {
        return $this->state(fn (array $attributes) => [
            'creator_user_id' => $creator->id,
        ]);
    }

    /**
     * Create a post with specific max players.
     */
    public function withMaxPlayers(int $maxPlayers): static
    {
        return $this->state(fn (array $attributes) => [
            'max_players' => $maxPlayers,
        ]);
    }
}

<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Platform\Enums\GameInviteStatus;
use App\Models\Game;
use App\Models\GameInvite;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GameInvite>
 */
class GameInviteFactory extends Factory
{
    protected $model = GameInvite::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'game_id' => Game::factory(),
            'sender_user_id' => User::factory(),
            'recipient_user_id' => User::factory(),
            'status' => GameInviteStatus::Pending,
            'message' => $this->faker->optional()->sentence(),
            'sent_at' => now(),
            'responded_at' => null,
            'expires_at' => now()->addDays(7),
        ];
    }

    /**
     * Create an accepted invite.
     */
    public function accepted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => GameInviteStatus::Accepted,
            'responded_at' => now(),
        ]);
    }

    /**
     * Create a declined invite.
     */
    public function declined(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => GameInviteStatus::Declined,
            'responded_at' => now(),
        ]);
    }

    /**
     * Create a canceled invite.
     */
    public function canceled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => GameInviteStatus::Canceled,
            'responded_at' => now(),
        ]);
    }

    /**
     * Create an expired invite.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => GameInviteStatus::Expired,
            'responded_at' => now(),
            'expires_at' => now()->subDay(),
        ]);
    }

    /**
     * Create an invite that will expire soon.
     */
    public function expiresSoon(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => now()->addHours(2),
        ]);
    }

    /**
     * Create an invite from a specific sender.
     */
    public function from(User $sender): static
    {
        return $this->state(fn (array $attributes) => [
            'sender_user_id' => $sender->id,
        ]);
    }

    /**
     * Create an invite to a specific recipient.
     */
    public function to(User $recipient): static
    {
        return $this->state(fn (array $attributes) => [
            'recipient_user_id' => $recipient->id,
        ]);
    }

    /**
     * Create an invite for a specific game.
     */
    public function for(Game $game): static
    {
        return $this->state(fn (array $attributes) => [
            'game_id' => $game->id,
        ]);
    }
}

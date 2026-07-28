<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Community\Enums\TicketState;
use App\Community\Enums\TicketType;
use App\Models\Achievement;
use App\Models\Leaderboard;
use App\Models\Ticket;
use App\Platform\Enums\TicketableType;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<Ticket>
 */
class TicketFactory extends Factory
{
    protected $model = Ticket::class;

    public function definition(): array
    {
        return [
            'ticketable_type' => TicketableType::Achievement->value,
            'body' => $this->faker->sentence(),
            'type' => TicketType::DidNotTrigger,
            'state' => TicketState::Open,
            'created_at' => Carbon::now()->subDays(3),
            'hardcore' => true,
        ];
    }

    public function forAchievement(?Achievement $achievement = null): static
    {
        return $this->state(fn () => [
            'ticketable_type' => TicketableType::Achievement->value,
            'ticketable_id' => $achievement?->id,
            'type' => TicketType::DidNotTrigger,
        ]);
    }

    public function forLeaderboard(?Leaderboard $leaderboard = null): static
    {
        return $this->state(fn () => [
            'ticketable_type' => TicketableType::Leaderboard->value,
            'ticketable_id' => $leaderboard?->id,
            'type' => TicketType::DidNotStart,
        ]);
    }

    public function open(): static
    {
        return $this->state(fn () => ['state' => TicketState::Open]);
    }

    public function request(): static
    {
        return $this->state(fn () => ['state' => TicketState::Request]);
    }

    public function resolved(): static
    {
        return $this->state(fn () => [
            'state' => TicketState::Resolved,
            'resolved_at' => Carbon::now()->subDay(),
        ]);
    }

    public function closed(): static
    {
        return $this->state(fn () => [
            'state' => TicketState::Closed,
            'resolved_at' => Carbon::now()->subDay(),
        ]);
    }

    public function quarantined(): static
    {
        return $this->state(fn () => ['state' => TicketState::Quarantined]);
    }
}

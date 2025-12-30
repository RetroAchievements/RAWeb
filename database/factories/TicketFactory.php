<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Community\Enums\TicketState;
use App\Community\Enums\TicketType;
use App\Models\Ticket;
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
            'ticketable_type' => 'achievement',
            'body' => $this->faker->sentence(),
            'type' => TicketType::DidNotTrigger,
            'state' => TicketState::Open,
            'created_at' => Carbon::now()->subDays(3),
            'hardcore' => true,
        ];
    }
}

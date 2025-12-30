<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Community\Enums\TriggerTicketState;
use App\Community\Enums\TriggerTicketType;
use App\Models\TriggerTicket;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<TriggerTicket>
 */
class TriggerTicketFactory extends Factory
{
    protected $model = TriggerTicket::class;

    public function definition(): array
    {
        return [
            'ticketable_type' => 'achievement',
            'body' => $this->faker->sentence(),
            'type' => TriggerTicketType::DidNotTrigger,
            'state' => TriggerTicketState::Open,
            'created_at' => Carbon::now()->subDays(3),
            'hardcore' => true,
        ];
    }
}

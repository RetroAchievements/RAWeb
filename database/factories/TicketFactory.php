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

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'ReportNotes' => $this->faker->sentence(),
            'ReportType' => TicketType::DidNotTrigger,
            'ReportState' => TicketState::Open,
            'ReportedAt' => Carbon::now()->subDays(3),
            'Hardcore' => 1,
        ];
    }
}

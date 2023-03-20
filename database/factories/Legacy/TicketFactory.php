<?php

declare(strict_types=1);

namespace Database\Factories\Legacy;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;
use LegacyApp\Community\Enums\TicketState;
use LegacyApp\Community\Enums\TicketType;
use LegacyApp\Community\Models\Ticket;
use LegacyApp\Platform\Models\Achievement;

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

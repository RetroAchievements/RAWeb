<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Community\Enums\TicketSignalVerdict;
use App\Models\TicketVerdictAccuracyResult;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<TicketVerdictAccuracyResult>
 */
class TicketVerdictAccuracyResultFactory extends Factory
{
    protected $model = TicketVerdictAccuracyResult::class;

    public function definition(): array
    {
        return [
            'verdict' => TicketSignalVerdict::ProbablyNotDefect,
            'ticket_count' => 4000,
            'correct_count' => 3480,
            'window_started_at' => Carbon::now()->subDays(365),
            'window_ended_at' => Carbon::now(),
        ];
    }
}

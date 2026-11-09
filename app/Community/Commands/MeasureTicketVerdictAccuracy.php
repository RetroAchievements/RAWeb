<?php

declare(strict_types=1);

namespace App\Community\Commands;

use App\Models\TicketVerdictAccuracyResult;
use App\Platform\Actions\MeasureTicketVerdictAccuracyAction;
use Illuminate\Console\Command;

class MeasureTicketVerdictAccuracy extends Command
{
    protected $signature = 'ra:community:ticket:measure-verdict-accuracy';
    protected $description = 'Scores how often each ticket signal verdict held up on the tickets finished over the past year';

    public function handle(): void
    {
        $accuracyRows = (new MeasureTicketVerdictAccuracyAction())->execute();

        $this->table(
            ['Verdict', 'Finished tickets', 'Predicted correctly'],
            $accuracyRows->map(fn (TicketVerdictAccuracyResult $accuracyRow) => [
                $accuracyRow->verdict->value,
                $accuracyRow->ticket_count,
                $accuracyRow->correct_count,
            ]),
        );
    }
}

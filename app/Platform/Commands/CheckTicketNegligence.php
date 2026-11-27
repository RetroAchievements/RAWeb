<?php

declare(strict_types=1);

namespace App\Platform\Commands;

use App\Platform\Actions\DetectNegligentDevelopersAction;
use Illuminate\Console\Command;

class CheckTicketNegligence extends Command
{
    protected $signature = 'ra:platform:developer:check-ticket-negligence';
    protected $description = 'Queue alerts for developers with negligent tickets';

    public function __construct(
        private readonly DetectNegligentDevelopersAction $detectNegligentDevelopersAction,
    ) {
        parent::__construct();
    }

    public function handle(): void
    {
        $findingsCount = $this->detectNegligentDevelopersAction->execute();

        $this->info("Queued {$findingsCount} ticket negligence findings.");
    }
}

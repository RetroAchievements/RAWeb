<?php

declare(strict_types=1);

namespace App\Platform\Commands;

use App\Platform\Actions\DetectInactiveDevelopersAction;
use Illuminate\Console\Command;

class CheckDeveloperInactivity extends Command
{
    protected $signature = 'ra:platform:developer:check-inactivity';
    protected $description = 'Queue alerts for inactive full developers';

    public function __construct(
        private readonly DetectInactiveDevelopersAction $detectInactiveDevelopersAction,
    ) {
        parent::__construct();
    }

    public function handle(): void
    {
        $findingsCount = $this->detectInactiveDevelopersAction->execute();

        $this->info("Queued {$findingsCount} developer inactivity findings.");
    }
}

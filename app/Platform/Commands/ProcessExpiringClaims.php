<?php

declare(strict_types=1);

namespace App\Platform\Commands;

use App\Platform\Actions\ProcessExpiringClaimsAction;
use Exception;
use Illuminate\Console\Command;

class ProcessExpiringClaims extends Command
{
    protected $signature = 'ra:platform:claim:process-expiring';
    protected $description = 'Auto-extend or send emails notifying users of claims nearing expiration and expire overdue claims';

    public function __construct(
        private readonly ProcessExpiringClaimsAction $processExpiringClaimsAction,
    ) {
        parent::__construct();
    }

    /**
     * @throws Exception
     */
    public function handle(): void
    {
        $this->processExpiringClaimsAction->execute();
    }
}

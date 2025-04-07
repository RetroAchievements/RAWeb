<?php

declare(strict_types=1);

namespace App\Platform\Commands;

use App\Platform\Actions\SendClaimExpirationWarningEmailsAction;
use Exception;
use Illuminate\Console\Command;

class SendClaimExpirationWarningEmails extends Command
{
    protected $signature = 'ra:platform:claim:notify-expiry';
    protected $description = 'Send emails notifying users of claims nearing expiration';

    public function __construct(
        private readonly SendClaimExpirationWarningEmailsAction $sendClaimExpirationWarningEmailsAction,
    ) {
        parent::__construct();
    }

    /**
     * @throws Exception
     */
    public function handle(): void
    {
        $this->sendClaimExpirationWarningEmailsAction->execute();
    }
}

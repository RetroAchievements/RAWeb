<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Exception;
use Illuminate\Console\Command;

class DeleteExpiredEmailVerificationTokens extends Command
{
    protected $signature = 'ra:site:user:delete-expired-email-verification-tokens';
    protected $description = 'Delete expired email verification tokens';

    /**
     * @throws Exception
     */
    public function handle(): void
    {
        deleteExpiredEmailVerificationTokens();
    }
}

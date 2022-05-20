<?php

declare(strict_types=1);

namespace App\Site\Commands;

use Exception;
use Illuminate\Console\Command;

class DeleteOverdueUserAccounts extends Command
{
    protected $signature = 'ra:site:delete-overdue-user-accounts';
    protected $description = 'Delete overdue user accounts marked for deletion';

    /**
     * @throws Exception
     */
    public function handle(): void
    {
        deleteOverdueUserAccounts();
    }
}

<?php

declare(strict_types=1);

namespace App\Community\Commands;

use App\Community\Actions\SyncAotwWinnerDiscordRolesAction;
use Illuminate\Console\Command;

class SyncAotwWinnerDiscordRoles extends Command
{
    protected $signature = 'ra:community:sync-aotw-winner-discord-roles';
    protected $description = 'Sync Discord roles for current Achievement of the Week winners';

    public function __construct(
        private readonly SyncAotwWinnerDiscordRolesAction $syncAotwWinnerDiscordRolesAction,
    ) {
        parent::__construct();
    }

    public function handle(): void
    {
        $this->syncAotwWinnerDiscordRolesAction->execute();
    }
}

<?php

declare(strict_types=1);

namespace App\Community\Commands;

use App\Community\Actions\ProcessExpiredMutesAction;
use Exception;
use Illuminate\Console\Command;

class ProcessExpiredMutes extends Command
{
    protected $signature = 'ra:community:process-expired-mutes';
    protected $description = 'Remove muted_until and Discord Muted role from users whose mutes have expired';

    public function __construct(
        private readonly ProcessExpiredMutesAction $processExpiredMutesAction,
    ) {
        parent::__construct();
    }

    /**
     * @throws Exception
     */
    public function handle(): void
    {
        $this->processExpiredMutesAction->execute();
    }
}

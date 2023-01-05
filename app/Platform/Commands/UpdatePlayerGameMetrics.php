<?php

declare(strict_types=1);

namespace App\Platform\Commands;

use Illuminate\Console\Command;

class UpdatePlayerGameMetrics extends Command
{
    protected $signature = 'ra:platform:player:update-game-metrics';
    protected $description = '';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle(): void
    {
    }
}

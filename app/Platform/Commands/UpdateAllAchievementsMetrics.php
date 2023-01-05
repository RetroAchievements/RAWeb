<?php

declare(strict_types=1);

namespace App\Platform\Commands;

use Illuminate\Console\Command;

class UpdateAllAchievementsMetrics extends Command
{
    protected $signature = 'ra:platform:achievements:update-all-metrics';
    protected $description = "Batch update all achievements' metrics";

    public function __construct()
    {
        parent::__construct();
    }

    public function handle(): void
    {
    }
}

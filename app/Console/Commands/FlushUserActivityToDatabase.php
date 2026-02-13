<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Platform\Services\UserLastActivityService;
use Illuminate\Console\Command;

class FlushUserActivityToDatabase extends Command
{
    protected $signature = 'ra:site:user:flush-activity';
    protected $description = 'Flush user activity timestamps from Redis to the database';

    public function handle(UserLastActivityService $userActivityService): void
    {
        $count = $userActivityService->flushToDatabase();

        $message = "Flushed {$count} user activity records to database.";

        $this->info($message);
    }
}

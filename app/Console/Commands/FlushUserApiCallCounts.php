<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Platform\Services\UserApiCallCountService;
use Illuminate\Console\Command;

class FlushUserApiCallCounts extends Command
{
    protected $signature = 'ra:api:flush-call-counts';
    protected $description = 'Flush user API call counts from Redis to the database';

    public function handle(UserApiCallCountService $userApiCallCountService): void
    {
        $count = $userApiCallCountService->flushToDatabase();

        $message = "Flushed {$count} user API call counts to database.";

        $this->info($message);
    }
}

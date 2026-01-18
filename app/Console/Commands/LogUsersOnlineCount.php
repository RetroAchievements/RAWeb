<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\UsersOnlineCount;
use App\Platform\Services\UserLastActivityService;
use Illuminate\Console\Command;

class LogUsersOnlineCount extends Command
{
    protected $signature = 'ra:site:user:log-online-count';
    protected $description = 'Log users online count';

    public function handle(UserLastActivityService $userActivityService): void
    {
        $playersOnline = $userActivityService->countOnline(withinMinutes: 10);

        UsersOnlineCount::log($playersOnline);
    }
}

<?php

declare(strict_types=1);

namespace LegacyApp\Site\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use LegacyApp\Site\Models\User;

class LogUsersOnlineCount extends Command
{
    protected $signature = 'ra-legacy:site:log-online-users-count';
    protected $description = 'Log users online count';

    /**
     * @throws Exception
     */
    public function handle(): void
    {
        $playersOnline = User::where('LastLogin', '>', Carbon::now()->subMinutes(10))->count();

        file_put_contents(storage_path('logs/playersonline.log'), $playersOnline . PHP_EOL, FILE_APPEND);
    }
}

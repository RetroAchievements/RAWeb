<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use App\Models\UsersOnlineCount;
use Carbon\Carbon;
use Illuminate\Console\Command;

class LogUsersOnlineCount extends Command
{
    protected $signature = 'ra:site:user:log-online-count';
    protected $description = 'Log users online count';

    public function handle(): void
    {
        $playersOnline = User::where('last_activity_at', '>', Carbon::now()->subMinutes(10))->count();

        UsersOnlineCount::log($playersOnline);
        file_put_contents(storage_path('logs/playersonline.log'), $playersOnline . PHP_EOL, FILE_APPEND);
    }
}

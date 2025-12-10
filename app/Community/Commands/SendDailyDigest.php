<?php

declare(strict_types=1);

namespace App\Community\Commands;

use App\Community\Actions\SendDailyDigestAction;
use App\Community\Jobs\SendDailyDigestJob;
use App\Models\User;
use App\Models\UserDelayedSubscription;
use Illuminate\Console\Command;

class SendDailyDigest extends Command
{
    protected $signature = 'ra:community:send-daily-digest ' .
                           '{userId? : User ID or username. Usernames containing only numbers are ambiguous and must be referenced by user ID}';

    protected $description = 'Generates a daily digest email for the provided user';

    public function __construct(
        private readonly SendDailyDigestAction $sendDailyDigestAction,
    ) {
        parent::__construct();
    }

    public function handle(): void
    {
        $userId = $this->argument('userId');
        if ($userId) {
            $user = is_numeric($userId)
                ? User::find($userId)
                : User::whereName($userId)->first();

            if (!$user) {
                $this->error("Could not find user [$userId]");

                return;
            }

            $this->sendDailyDigestAction->execute($user);
        } else {
            $users = UserDelayedSubscription::query()
                ->select('user_id')
                ->distinct()
                ->pluck('user_id')
                ->toArray();

            foreach ($users as $userId) {
                SendDailyDigestJob::dispatch($userId)->onQueue('summary-emails');
            }
        }
    }
}

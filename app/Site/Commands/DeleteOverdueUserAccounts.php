<?php

declare(strict_types=1);

namespace App\Site\Commands;

use App\Site\Actions\ClearAccountDataAction;
use App\Site\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

class DeleteOverdueUserAccounts extends Command
{
    protected $signature = 'ra:site:user:delete-overdue-accounts';
    protected $description = 'Delete overdue user accounts marked for deletion';

    public function __construct(
        private readonly ClearAccountDataAction $clearAccountDataAction,
    ) {
        parent::__construct();
    }

    /**
     * @throws Exception
     */
    public function handle(): void
    {
        $threshold = Carbon::today()->setTime(8, 0)->subWeeks(2);

        /** @var Collection<int, User> $users */
        $users = User::where('DeleteRequested', '<=', $threshold)
            ->orderBy('DeleteRequested')
            ->get();

        foreach ($users as $user) {
            $this->clearAccountDataAction->execute($user);
        }
    }
}

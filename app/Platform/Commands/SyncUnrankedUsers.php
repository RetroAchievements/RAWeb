<?php

declare(strict_types=1);

namespace App\Platform\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SyncUnrankedUsers extends Command
{
    protected $signature = 'ra:sync:unranked-users';
    protected $description = 'Sync users with unranked_at or Deleted values to the unranked_users table';

    public function handle(): void
    {
        $this->info('Starting sync of unranked users...');

        // Clear the unranked_users table.
        DB::table('unranked_users')->truncate();
        $this->info('Cleared unranked_users table.');

        // Get the users.
        $query = User::query()
            ->where(function ($q) {
                $q->whereNotNull('unranked_at')->orWhereNotNull('Deleted');
            });

        $total = $query->count();
        $this->info("Found {$total} unranked users to sync.");

        if ($total === 0) {
            $this->info('No unranked users to sync.');

            return;
        }

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $query->chunkById(1000, function ($users) use ($bar) {
            $inserts = [];

            /** @var User $user */
            foreach ($users as $user) {
                $inserts[] = ['user_id' => $user->id];
                $bar->advance();
            }

            if (!empty($inserts)) {
                DB::table('unranked_users')->insert($inserts);
            }
        }, 'ID');

        $bar->finish();
        $this->newLine(2);

        $this->info("Done. Synced {$total} unranked users.");
    }
}

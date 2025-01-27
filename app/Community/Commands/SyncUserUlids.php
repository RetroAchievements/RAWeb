<?php

declare(strict_types=1);

namespace App\Community\Commands;

use App\Models\User;
use Carbon\Carbon;
use DB;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class SyncUserUlids extends Command
{
    protected $signature = 'ra:sync:user-ulids';

    protected $description = 'Sync user ulid field values';

    public function handle(): void
    {
        $total = User::whereNull('ulid')->count();

        if ($total === 0) {
            $this->info('No records need ULIDs.');

            return;
        }

        $progressBar = $this->output->createProgressBar($total);
        $progressBar->start();

        User::withTrashed()
            ->whereNull('ulid')
            ->chunkById(4000, function ($users) use ($progressBar, &$lastTimestamp) {
                $updates = [];

                /** @var User $user */
                foreach ($users as $user) {
                    // Increment timestamp for each ULID to ensure some variance.
                    $lastTimestamp++;

                    $ulid = (string) Str::ulid(Carbon::createFromTimestamp($lastTimestamp));

                    $updates[] = [
                        'ID' => $user->id,
                        'ulid' => $ulid,
                    ];
                }

                // Perform a batch update for speed.
                DB::table('UserAccounts')
                    ->upsert(
                        $updates,
                        ['ID'],
                        ['ulid']
                    );

                $progressBar->advance(count($updates));
            });

        $progressBar->finish();

        $this->newLine();
        $this->info('Done.');
    }
}

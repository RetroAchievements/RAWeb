<?php

declare(strict_types=1);

namespace App\Community\Commands;

use App\Models\User;
use DB;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class SyncUserUlids extends Command
{
    protected $signature = 'ra:sync:user-ulids';

    protected $description = 'Sync user ulid field values';

    public function handle(): void
    {
        $total = User::count();

        $progressBar = $this->output->createProgressBar($total);
        $progressBar->start();

        User::withTrashed()
            ->chunkById(4000, function ($users) use ($progressBar) {
                $updates = [];

                /** @var User $user */
                foreach ($users as $user) {
                    $milliseconds = rand(1, 20);
                    $milliseconds %= 1000;

                    $timestamp = $user->Created->clone()->addMilliseconds($milliseconds);
                    $ulid = (string) Str::ulid($timestamp);

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

<?php

declare(strict_types=1);

namespace App\Platform\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SyncUnrankedUsersTable extends Command
{
    protected $signature = 'ra:sync:unranked-users';
    protected $description = 'Rebuild the unranked_users table from users.unranked_at';

    public function handle(): void
    {
        $this->info('Rebuilding unranked_users table...');

        DB::table('unranked_users')->truncate();

        $userIds = User::withTrashed()->whereNotNull('unranked_at')->pluck('id');

        $inserted = 0;
        foreach ($userIds->chunk(1000) as $chunk) {
            DB::table('unranked_users')->insert(
                $chunk->map(fn ($id) => ['user_id' => $id])->toArray()
            );
            $inserted += $chunk->count();
        }

        $this->info("Inserted {$inserted} entries from users.unranked_at.");
        $this->info('Done.');
    }
}

<?php

declare(strict_types=1);

namespace LegacyApp\Site\Commands;

use Eloquent;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use LegacyApp\Site\Models\User;

class UpdateUserTimestamps extends Command
{
    protected $signature = 'ra-legacy:site:update-user-timestamps';

    protected $description = 'Calculates missing and/or incorrect timestamps for users';

    public function handle(): void
    {
        // Scott's account was created on the day of the domain registration
        User::where('User', 'Scott')
            ->update([
                'Created' => '2012-10-03 13:37:00',
                'Updated' => DB::raw('Updated'),
            ]);

        // https://web.archive.org/web/20121121150009/http://retroachievements.org/
        User::whereIn('User', [
            'Dave',
            'Jimmy',
            'Batman',
            'Steve',
            'Michael',
            'poip',
            'Sonic',
            'qwer',
            'Bob',
            'Dan',
            'Jim',
            'qweqwe',
            'qwe',
        ])
            ->update([
                'Created' => '2012-11-21 13:37:00',
                'Updated' => DB::raw('Updated'),
            ]);

        // Teario - first achievement unlock
        User::where('User', 'Teario')
            ->update([
                'Created' => '2012-12-29 13:37:00',
                'Updated' => DB::raw('Updated'),
            ]);

        // Same as nightgoat99
        User::where('User', 'nightgoat')
            ->update([
                'Created' => '2013-03-02 04:17:05',
                'Updated' => DB::raw('Updated'),
            ]);

        // Iterate through user accounts by ID, descending, fill gaps and correct order of timestamps.
        Eloquent::unguard();
        $maxId = User::whereNull('Created')->max('ID') + 1; // 106605
        $users = User::withTrashed()
            ->where('ID', '<=', $maxId)
            ->orderByDesc('ID')
            ->get(['ID', 'User', 'Created']);
        $progressBar = $this->output->createProgressBar($users->count());
        $progressBar->start();
        $timestampMemento = null;
        $updated = 0;
        /** @var User $user */
        foreach ($users as $user) {
            // Apply previous timestamp if it's earlier than the current or no Created timestamp exists.
            if ($timestampMemento && (!$user->Created || $user->Created > $timestampMemento)) {
                // $this->line('Update ' . $user->User . ' from "' . $user->Created . '" to "' . $timestampMemento . '"');
                $user->update([
                    'Created' => $timestampMemento,
                ]);
                $updated++;
            } else {
                $timestampMemento = $user->Created;
            }
            $progressBar->advance();
        }
        $progressBar->finish();

        $this->line('Updated ' . $updated . ' timestamps');
    }
}

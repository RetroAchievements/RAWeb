<?php

declare(strict_types=1);

namespace App\Site\Commands;

use App\Site\Models\User;
use Eloquent;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class UpdateUserTimestamps extends Command
{
    protected $signature = 'ra:site:update-user-timestamps';

    protected $description = 'Calculates missing and/or incorrect timestamps for users';

    public function handle(): void
    {
        $this->updateInitialAccountCreationDates();

        $this->interpolateAccountCreationDates();
    }

    private function updateInitialAccountCreationDates(): void
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

        $this->info('Updated initial account creation timestamps');
    }

    /**
     * Iterate through user accounts by ID, descending, fill gaps and correct order of timestamps.
     */
    private function interpolateAccountCreationDates(): void
    {
        Eloquent::unguard();
        // 106605, created on 2019-09-22 is the first user to have a real creation timestamp
        $maxId = 106605;

        $this->line('Fetching forum topic comment timestamps...');
        $forumTopicTimestamps = User::withTrashed()
            ->selectRaw('UserAccounts.ID, UserAccounts.User, UserAccounts.Created, MIN(ForumTopicComment.DateCreated) ForumTopicTimestamp')
            ->leftJoin('ForumTopicComment', 'Author', '=', 'UserAccounts.User')
            ->where('UserAccounts.ID', '<=', $maxId)
            // Manually deleted accounts which have old forum topic comments and would mess with the sequence
            ->whereNotIn('UserAccounts.ID', [
                92596,
                92599,
                92600,
                92601,
                92602,
            ])
            ->whereRaw('ForumTopicComment.DateCreated < UserAccounts.Created')
            ->groupByRaw('UserAccounts.ID, UserAccounts.User, UserAccounts.Created')
            ->orderByDesc('UserAccounts.ID')
            ->get()
            ->keyBy('ID');
        $this->info('Found ' . $forumTopicTimestamps->count() . ' earlier forum topic comment timestamps');

        $this->line('Fetching comment timestamps...');
        $commentTimestamps = User::withTrashed()
            ->selectRaw('UserAccounts.ID, UserAccounts.User, UserAccounts.Created, MIN(Comment.Submitted) CommentTimestamp')
            ->leftJoin('Comment', 'UserID', '=', 'UserAccounts.ID')
            ->where('UserAccounts.ID', '<=', $maxId)
            ->whereRaw('Comment.Submitted < UserAccounts.Created')
            ->groupByRaw('UserAccounts.ID, UserAccounts.User, UserAccounts.Created')
            ->orderByDesc('UserAccounts.ID')
            ->get()
            ->keyBy('ID');
        $this->info('Found ' . $commentTimestamps->count() . ' earlier comment timestamps');

        $this->line('Interpolate account creation timestamps...');
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
            $timestampMemento ??= $user->Created;

            // Take earlier forum topic comment timestamp if exists
            if ($forumTopicTimestamps->has($user->ID)) {
                $forumTopicTimestamp = $forumTopicTimestamps->get($user->ID)['ForumTopicTimestamp'];
                $timestampMemento = $timestampMemento->isAfter($forumTopicTimestamp)
                    ? Carbon::parse($forumTopicTimestamp)
                    : $timestampMemento;
                // $this->line('[' . $user->ID . '] FTC! ' . $timestampMemento->format('Y-m-d H:i:s'));
            }

            // Take earlier comment timestamp if exists
            if ($commentTimestamps->has($user->ID)) {
                $commentTimestamp = $commentTimestamps->get($user->ID)['CommentTimestamp'];
                $timestampMemento = $timestampMemento->isAfter($commentTimestamp)
                    ? Carbon::parse($commentTimestamp)
                    : $timestampMemento;
                // $this->line('[' . $user->ID . '] C! ' . $timestampMemento->format('Y-m-d H:i:s'));
            }

            // Apply previous timestamp if it's earlier than the current or no Created timestamp exists.
            if (!$user->Created || $user->Created->isAfter($timestampMemento)) {
                $user->update([
                    'Created' => $timestampMemento,
                ]);
                $updated++;
                // $this->line('[' . $user->ID . '] Update ' . $user->User . ' from "' . $user->Created . '" to "' . $timestampMemento . '"');
            }

            if ($user->Created && $user->Created->isBefore($timestampMemento)) {
                $timestampMemento = $user->Created;
                // $this->line('[' . $user->ID . '] Use ' . $timestampMemento->format('Y-m-d H:i:s'));
            }

            $progressBar->advance();
        }
        $progressBar->finish();

        $this->info(PHP_EOL . 'Updated ' . $updated . ' account creation timestamps');
    }
}

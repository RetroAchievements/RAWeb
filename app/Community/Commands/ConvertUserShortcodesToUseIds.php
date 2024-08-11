<?php

declare(strict_types=1);

namespace App\Community\Commands;

use App\Models\ForumTopicComment;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ConvertUserShortcodesToUseIds extends Command
{
    protected $signature = 'ra:forum:migrate-user-shortcodes {--undo}';

    protected $description = 'Migrate all [user=username] shortcodes to [user=id]';

    public function handle(): void
    {
        if ($this->option('undo')) {
            $this->info("\nUndoing the migration of [user=username] shortcodes to [user=id].");
            $this->undoMigration();
        } else {
            $this->info("\nStarting the migration of [user=username] shortcodes to [user=id].");
            $this->migrateShortcodes();
        }
    }

    private function migrateShortcodes(): void
    {
        // Get the total count of comments that need to be processed.
        $totalComments = ForumTopicComment::where('Payload', 'like', '%[user=%')->count();

        $progressBar = $this->output->createProgressBar($totalComments);
        ForumTopicComment::where('Payload', 'like', '%[user=%')->chunkById(1000, function ($forumTopicComments) use ($progressBar) {
            // Collect all usernames to fetch user IDs in bulk.
            $usernames = [];

            foreach ($forumTopicComments as $forumTopicComment) {
                preg_match_all('/\[user=([^\]]+)\]/', $forumTopicComment->Payload, $matches);
                $usernames = array_merge($usernames, array_map('strtolower', $matches[1]));
            }

            // Remove duplicates and fetch user IDs.
            $usernames = array_unique($usernames);
            $users = User::whereIn(DB::raw('LOWER(User)'), $usernames)->get()->keyBy(fn ($user) => strtolower($user->User));

            // Process each comment.
            foreach ($forumTopicComments as $forumTopicComment) {
                $originalPayload = $forumTopicComment->Payload;
                $updatedPayload = preg_replace_callback('/\[user=([^\]]+)\]/i', function ($matches) use ($users) {
                    $username = strtolower($matches[1]);
                    $user = $users->get($username);

                    return $user ? "[user={$user->ID}]" : $matches[0];
                }, $forumTopicComment->Payload);

                // Update the comment in the DB only if it has actually changed.
                if ($originalPayload !== $updatedPayload) {
                    DB::table('ForumTopicComment')
                        ->where('ID', $forumTopicComment->id)
                        ->update(['Payload' => $updatedPayload]);
                }
            }

            $progressBar->advance(count($forumTopicComments));
        });
        $progressBar->finish();

        $this->info("\nMigration completed successfully.");
    }

    private function undoMigration(): void
    {
        // Get the total count of comments that need to be processed.
        $totalComments = ForumTopicComment::where('Payload', 'like', '%[user=%')->count();

        $progressBar = $this->output->createProgressBar($totalComments);
        ForumTopicComment::where('Payload', 'like', '%[user=%')->chunkById(1000, function ($forumTopicComments) use ($progressBar) {
            // Collect all user IDs to fetch usernames in bulk.
            $userIds = [];

            foreach ($forumTopicComments as $forumTopicComment) {
                preg_match_all('/\[user=(\d+)\]/i', $forumTopicComment->Payload, $matches);
                $userIds = array_merge($userIds, $matches[1]);
            }

            // Remove duplicates and fetch usernames.
            $userIds = array_unique($userIds);
            $users = User::whereIn('ID', $userIds)->get()->keyBy('ID');

            // Process each comment.
            foreach ($forumTopicComments as $forumTopicComment) {
                $originalPayload = $forumTopicComment->Payload;
                $updatedPayload = preg_replace_callback('/\[user=(\d+)\]/i', function ($matches) use ($users) {
                    $userId = (int) $matches[1];
                    $user = $users->get($userId);

                    return $user ? "[user={$user->User}]" : $matches[0];
                }, $forumTopicComment->Payload);

                // Update the comment in the DB only if it has actually changed.
                if ($originalPayload !== $updatedPayload) {
                    DB::table('ForumTopicComment')
                        ->where('ID', $forumTopicComment->id)
                        ->update(['Payload' => $updatedPayload]);
                }
            }

            $progressBar->advance(count($forumTopicComments));
        });
        $progressBar->finish();

        $this->info("\nUndo completed successfully.");
    }
}

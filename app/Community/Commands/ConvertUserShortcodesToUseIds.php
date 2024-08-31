<?php

declare(strict_types=1);

namespace App\Community\Commands;

use App\Models\ForumTopicComment;
use App\Models\Message;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ConvertUserShortcodesToUseIds extends Command
{
    protected $signature = 'ra:community:migrate-user-shortcodes {--undo}';

    protected $description = 'Migrate all [user=username] shortcodes to [user=id]';

    public function handle(): void
    {
        if ($this->option('undo')) {
            $this->info("\nUndoing the migration of [user=username] shortcodes to [user=id].");

            $this->undoForumMigration();
            $this->undoMessagesMigration();
        } else {
            $this->info("\nStarting the migration of [user=username] shortcodes to [user=id].");

            $this->migrateForumShortcodes();
            $this->migrateMessageShortcodes();
        }
    }

    private function migrateForumShortcodes(): void
    {
        // Get the total count of comments that need to be processed.
        $totalComments = ForumTopicComment::where('Payload', 'like', '%[user=%')->count();

        $progressBar = $this->output->createProgressBar($totalComments);
        ForumTopicComment::where('Payload', 'like', '%[user=%')->chunkById(1000, function ($forumTopicComments) use ($progressBar) {
            // Collect all usernames to fetch user IDs in bulk.
            $usernames = [];

            /** @var ForumTopicComment $forumTopicComment */
            foreach ($forumTopicComments as $forumTopicComment) {
                preg_match_all('/\[user=([^\]]+)\]/', $forumTopicComment->Payload, $matches);
                $usernames = array_merge($usernames, array_map('strtolower', $matches[1]));
            }

            // Remove duplicates and fetch user IDs.
            $usernames = array_unique($usernames);
            $users = User::whereIn(DB::raw('LOWER(User)'), $usernames)->get()->keyBy(fn ($user) => strtolower($user->User));

            // Process each comment.
            /** @var ForumTopicComment $forumTopicComment */
            foreach ($forumTopicComments as $forumTopicComment) {
                $originalPayload = $forumTopicComment->Payload;
                $updatedPayload = preg_replace_callback('/\[user=([^\]]+)\]/i', function ($matches) use ($users) {
                    $username = strtolower($matches[1]);
                    $user = $users->get($username);

                    return $user ? "[user={$user->ID}]" : $matches[0];
                }, $forumTopicComment->Payload);

                // Update the comment in the DB only if it has actually changed.
                // Use `DB` so we don't change the `updated_at` value.
                if ($originalPayload !== $updatedPayload) {
                    DB::table('ForumTopicComment')
                        ->where('ID', $forumTopicComment->id)
                        ->update(['Payload' => $updatedPayload]);
                }
            }

            $progressBar->advance(count($forumTopicComments));
        });
        $progressBar->finish();

        $this->info("\nForumTopicComments migration completed successfully.");
    }

    private function migrateMessageShortcodes(): void
    {
        // Get the total count of messages that need to be processed.
        $totalMessages = Message::where('body', 'like', '%[user=%')->count();

        $progressBar = $this->output->createProgressBar($totalMessages);
        Message::where('body', 'like', '%[user=%')->chunkById(1000, function ($messages) use ($progressBar) {
            // Collect all usernames to fetch user IDs in bulk.
            $usernames = [];

            /** @var Message $message */
            foreach ($messages as $message) {
                preg_match_all('/\[user=([^\]]+)\]/', $message->body, $matches);
                $usernames = array_merge($usernames, array_map('strtolower', $matches[1]));
            }

            // Remove duplicates and fetch user IDs.
            $usernames = array_unique($usernames);
            $users = User::whereIn(DB::raw('LOWER(User)'), $usernames)->get()->keyBy(fn ($user) => strtolower($user->User));

            // Process each message.
            /** @var Message $message */
            foreach ($messages as $message) {
                $originalBody = $message->body;
                $updatedBody = preg_replace_callback('/\[user=([^\]]+)\]/i', function ($matches) use ($users) {
                    $username = strtolower($matches[1]);
                    $user = $users->get($username);

                    return $user ? "[user={$user->ID}]" : $matches[0];
                }, $message->body);

                // Update the message in the DB only if it has actually changed.
                // Use `DB` so we don't change the `updated_at` value.
                if ($originalBody !== $updatedBody) {
                    DB::table('messages')
                        ->where('id', $message->id)
                        ->update(['body' => $updatedBody]);
                }
            }

            $progressBar->advance(count($messages));
        });
        $progressBar->finish();

        $this->info("\nMessages migration completed successfully.");
    }

    private function undoForumMigration(): void
    {
        // Get the total count of comments that need to be processed.
        $totalComments = ForumTopicComment::where('Payload', 'like', '%[user=%')->count();

        $progressBar = $this->output->createProgressBar($totalComments);
        ForumTopicComment::where('Payload', 'like', '%[user=%')->chunkById(1000, function ($forumTopicComments) use ($progressBar) {
            // Collect all user IDs to fetch usernames in bulk.
            $userIds = [];

            /** @var ForumTopicComment $forumTopicComment */
            foreach ($forumTopicComments as $forumTopicComment) {
                preg_match_all('/\[user=(\d+)\]/i', $forumTopicComment->Payload, $matches);
                $userIds = array_merge($userIds, $matches[1]);
            }

            // Remove duplicates and fetch usernames.
            $userIds = array_unique($userIds);
            $users = User::whereIn('ID', $userIds)->get()->keyBy('ID');

            // Process each comment.
            /** @var ForumTopicComment $forumTopicComment */
            foreach ($forumTopicComments as $forumTopicComment) {
                $originalPayload = $forumTopicComment->Payload;
                $updatedPayload = preg_replace_callback('/\[user=(\d+)\]/i', function ($matches) use ($users) {
                    $userId = (int) $matches[1];
                    $user = $users->get($userId);

                    return $user ? "[user={$user->User}]" : $matches[0];
                }, $forumTopicComment->Payload);

                // Update the comment in the DB only if it has actually changed.
                // Use `DB` so we don't change the `updated_at` value.
                if ($originalPayload !== $updatedPayload) {
                    DB::table('ForumTopicComment')
                        ->where('ID', $forumTopicComment->id)
                        ->update(['Payload' => $updatedPayload]);
                }
            }

            $progressBar->advance(count($forumTopicComments));
        });
        $progressBar->finish();

        $this->info("\nForumTopicComments undo completed successfully.");
    }

    private function undoMessagesMigration(): void
    {
        // Get the total count of messages that need to be processed.
        $totalMessages = Message::where('body', 'like', '%[user=%')->count();

        $progressBar = $this->output->createProgressBar($totalMessages);
        Message::where('body', 'like', '%[user=%')->chunkById(1000, function ($messages) use ($progressBar) {
            // Collect all user IDs to fetch usernames in bulk.
            $userIds = [];

            /** @var Message $message */
            foreach ($messages as $message) {
                preg_match_all('/\[user=(\d+)\]/i', $message->body, $matches);
                $userIds = array_merge($userIds, $matches[1]);
            }

            // Remove duplicates and fetch usernames.
            $userIds = array_unique($userIds);
            $users = User::whereIn('ID', $userIds)->get()->keyBy('ID');

            // Process each message.
            /** @var Message $message */
            foreach ($messages as $message) {
                $originalBody = $message->body;
                $updatedBody = preg_replace_callback('/\[user=(\d+)\]/i', function ($matches) use ($users) {
                    $userId = (int) $matches[1];
                    $user = $users->get($userId);

                    return $user ? "[user={$user->User}]" : $matches[0];
                }, $message->body);

                // Update the comment in the DB only if it has actually changed.
                // Use `DB` so we don't change the `updated_at` value.
                if ($originalBody !== $updatedBody) {
                    DB::table('messages')
                        ->where('id', $message->id)
                        ->update(['body' => $updatedBody]);
                }
            }

            $progressBar->advance(count($messages));
        });
        $progressBar->finish();

        $this->info("\nmessages undo completed successfully.");
    }
}

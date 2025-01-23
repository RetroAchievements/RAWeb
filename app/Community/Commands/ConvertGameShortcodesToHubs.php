<?php

declare(strict_types=1);

namespace App\Community\Commands;

use App\Models\ForumTopicComment;
use App\Models\Game;
use App\Models\GameSet;
use App\Models\Message;
use App\Platform\Enums\GameSetType;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ConvertGameShortcodesToHubs extends Command
{
    protected $signature = 'ra:community:migrate-game-shortcodes {--undo}';

    protected $description = 'Migrate all [game=id] shortcodes for legacy hubs to [hub=id]';

    public function handle(): void
    {
        if ($this->option('undo')) {
            $this->info("\nUndoing the migration of [hub=id] shortcodes back to [game=id].");

            $this->undoForumMigration();
            $this->undoMessagesMigration();
        } else {
            $this->info("\nStarting the migration of legacy hub [game=id] shortcodes to [hub=id].");

            $this->migrateForumShortcodes();
            $this->migrateMessageShortcodes();
        }
    }

    private function migrateForumShortcodes(): void
    {
        // Get the total count of comments that need to be processed.
        $totalComments = ForumTopicComment::where('body', 'like', '%[game=%')->count();

        $progressBar = $this->output->createProgressBar($totalComments);
        ForumTopicComment::where('body', 'like', '%[game=%')->chunkById(1000, function ($forumTopicComments) use ($progressBar) {
            // Collect all game IDs to fetch hub mappings in bulk.
            $gameIds = [];

            /** @var ForumTopicComment $forumTopicComment */
            foreach ($forumTopicComments as $forumTopicComment) {
                preg_match_all('/\[game=(\d+)\]/', $forumTopicComment->body, $matches);
                $gameIds = array_merge($gameIds, $matches[1]);
            }

            // Remove duplicates and fetch games that are legacy hubs.
            $gameIds = array_unique($gameIds);
            $legacyHubs = Game::whereIn('ID', $gameIds)
                ->where('ConsoleID', 100)
                ->get()
                ->keyBy('ID');

            // Fetch corresponding hub IDs for these legacy games.
            $hubMappings = GameSet::where('type', GameSetType::Hub)
                ->whereIn('game_id', $legacyHubs->pluck('ID'))
                ->get()
                ->keyBy('game_id');

            // Process each comment.
            /** @var ForumTopicComment $forumTopicComment */
            foreach ($forumTopicComments as $forumTopicComment) {
                $originalPayload = $forumTopicComment->body;
                $updatedPayload = preg_replace_callback('/\[game=(\d+)\]/', function ($matches) use ($legacyHubs, $hubMappings) {
                    $gameId = (int) $matches[1];

                    // Only replace if it's a legacy hub and we have a mapping.
                    if ($legacyHubs->has($gameId) && $hubMappings->has($gameId)) {
                        return "[hub={$hubMappings->get($gameId)->id}]";
                    }

                    return $matches[0];
                }, $forumTopicComment->body);

                // Update the comment in the DB only if it has actually changed.
                // Use `DB` so we don't change the `updated_at` value.
                if ($originalPayload !== $updatedPayload) {
                    $forumTopicComment->body = $updatedPayload;
                    $forumTopicComment->timestamps = false;
                    $forumTopicComment->save();
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
        $totalMessages = Message::where('body', 'like', '%[game=%')->count();

        $progressBar = $this->output->createProgressBar($totalMessages);
        Message::where('body', 'like', '%[game=%')->chunkById(1000, function ($messages) use ($progressBar) {
            // Collect all game IDs to fetch hub mappings in bulk.
            $gameIds = [];

            /** @var Message $message */
            foreach ($messages as $message) {
                preg_match_all('/\[game=(\d+)\]/', $message->body, $matches);
                $gameIds = array_merge($gameIds, $matches[1]);
            }

            // Remove duplicates and fetch games that are legacy hubs.
            $gameIds = array_unique($gameIds);
            $legacyHubs = Game::whereIn('ID', $gameIds)
                ->where('ConsoleID', 100)
                ->get()
                ->keyBy('ID');

            // Fetch corresponding hub IDs for these legacy games.
            $hubMappings = GameSet::where('type', GameSetType::Hub)
                ->whereIn('game_id', $legacyHubs->pluck('ID'))
                ->get()
                ->keyBy('game_id');

            // Process each message.
            /** @var Message $message */
            foreach ($messages as $message) {
                $originalBody = $message->body;
                $updatedBody = preg_replace_callback('/\[game=(\d+)\]/', function ($matches) use ($legacyHubs, $hubMappings) {
                    $gameId = (int) $matches[1];

                    // Only replace if it's a legacy hub and we have a mapping.
                    if ($legacyHubs->has($gameId) && $hubMappings->has($gameId)) {
                        return "[hub={$hubMappings->get($gameId)->id}]";
                    }

                    return $matches[0];
                }, $message->body);

                // Update the message in the DB only if it has actually changed.
                // Use `DB` so we don't change the `updated_at` value.
                if ($originalBody !== $updatedBody) {
                    $message->body = $updatedBody;
                    $message->save();
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
        $totalComments = ForumTopicComment::where('body', 'like', '%[hub=%')->count();

        $progressBar = $this->output->createProgressBar($totalComments);
        ForumTopicComment::where('body', 'like', '%[hub=%')->chunkById(1000, function ($forumTopicComments) use ($progressBar) {
            // Collect all hub IDs to fetch game mappings in bulk.
            $hubIds = [];

            /** @var ForumTopicComment $forumTopicComment */
            foreach ($forumTopicComments as $forumTopicComment) {
                preg_match_all('/\[hub=(\d+)\]/', $forumTopicComment->body, $matches);
                $hubIds = array_merge($hubIds, $matches[1]);
            }

            // Remove duplicates and fetch hub mappings.
            $hubIds = array_unique($hubIds);
            $hubMappings = GameSet::whereIn('id', $hubIds)
                ->where('type', GameSetType::Hub)
                ->get()
                ->keyBy('id');

            // Process each comment.
            /** @var ForumTopicComment $forumTopicComment */
            foreach ($forumTopicComments as $forumTopicComment) {
                $originalPayload = $forumTopicComment->body;
                $updatedPayload = preg_replace_callback('/\[hub=(\d+)\]/', function ($matches) use ($hubMappings) {
                    $hubId = (int) $matches[1];
                    $hubMapping = $hubMappings->get($hubId);

                    return $hubMapping ? "[game={$hubMapping->game_id}]" : $matches[0];
                }, $forumTopicComment->body);

                // Update the comment in the DB only if it has actually changed.
                // Use `DB` so we don't change the `updated_at` value.
                if ($originalPayload !== $updatedPayload) {
                    $forumTopicComment->body = $updatedPayload;
                    $forumTopicComment->timestamps = false;
                    $forumTopicComment->save();
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
        $totalMessages = Message::where('body', 'like', '%[hub=%')->count();

        $progressBar = $this->output->createProgressBar($totalMessages);
        Message::where('body', 'like', '%[hub=%')->chunkById(1000, function ($messages) use ($progressBar) {
            // Collect all hub IDs to fetch game mappings in bulk.
            $hubIds = [];

            /** @var Message $message */
            foreach ($messages as $message) {
                preg_match_all('/\[hub=(\d+)\]/', $message->body, $matches);
                $hubIds = array_merge($hubIds, $matches[1]);
            }

            // Remove duplicates and fetch hub mappings.
            $hubIds = array_unique($hubIds);
            $hubMappings = GameSet::whereIn('id', $hubIds)
                ->where('type', GameSetType::Hub)
                ->get()
                ->keyBy('id');

            // Process each message.
            /** @var Message $message */
            foreach ($messages as $message) {
                $originalBody = $message->body;
                $updatedBody = preg_replace_callback('/\[hub=(\d+)\]/', function ($matches) use ($hubMappings) {
                    $hubId = (int) $matches[1];
                    $hubMapping = $hubMappings->get($hubId);

                    return $hubMapping ? "[game={$hubMapping->game_id}]" : $matches[0];
                }, $message->body);

                // Update the message in the DB only if it has actually changed.
                // Use `DB` so we don't change the `updated_at` value.
                if ($originalBody !== $updatedBody) {
                    $message->body = $updatedBody;
                    $message->save();
                }
            }

            $progressBar->advance(count($messages));
        });
        $progressBar->finish();

        $this->info("\nMessages undo completed successfully.");
    }
}

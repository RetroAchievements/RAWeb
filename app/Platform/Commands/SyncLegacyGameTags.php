<?php

declare(strict_types=1);

namespace App\Platform\Commands;

use App\Models\Game;
use App\Platform\Actions\SyncGameTagsFromTitleAction;
use Illuminate\Console\Command;
use Spatie\Tags\Tag;

class SyncLegacyGameTags extends Command
{
    protected $signature = 'ra:sync:legacy-game-tags';
    protected $description = 'Sync legacy game tags (from game titles) to the tags table.';

    protected array $legacyTags = [
        'Demo',
        'Hack',
        'Homebrew',
        'Prototype',
        'Test Kit',
        'Unlicensed',
        'Z',
    ];

    public function handle(): void
    {
        // Upsert the actual tags themselves into the DB.
        foreach ($this->legacyTags as $tag) {
            $exists = Tag::where('type', 'game')->where('name->en', $tag)->exists();
            Tag::findOrCreate($tag, 'game');

            if (!$exists) {
                $this->info("Created new tag: {$tag}");
            } else {
                $this->line("Tag already exists: {$tag}");
            }
        }

        // Now that we know real tags are in the DB, process each legacy tag.
        foreach ($this->legacyTags as $legacyTag) {
            $this->info("Processing {$legacyTag} tagged games...");

            $games = Game::where('Title', 'LIKE', "~{$legacyTag}~%")->get();

            $progressBar = $this->output->createProgressBar($games->count());

            foreach ($games as $game) {
                (new SyncGameTagsFromTitleAction())->execute($game, $game->title);

                $progressBar->advance();
            }

            $progressBar->finish();
            $this->newLine();
            $this->info("Completed processing {$games->count()} games with tag {$legacyTag}.");
        }

        $this->info('All legacy tags have been synced.');
    }
}

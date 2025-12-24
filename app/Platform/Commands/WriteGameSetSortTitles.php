<?php

declare(strict_types=1);

namespace App\Platform\Commands;

use App\Models\GameSet;
use App\Platform\Actions\WriteGameSetSortTitleAction;
use App\Platform\Enums\GameSetType;
use Illuminate\Console\Command;

class WriteGameSetSortTitles extends Command
{
    protected $signature = "ra:platform:write-game-set-sort-titles
                            {gameSetId? : Target a single game set}";
    protected $description = 'Write sort titles for game sets (hubs) derived from their titles';

    public function handle(): void
    {
        $gameSetId = $this->argument('gameSetId');
        if ($gameSetId !== null) {
            $gameSet = GameSet::findOrFail($gameSetId);

            $this->info("\nUpserting a sort title for [{$gameSet->id}:{$gameSet->title}].");

            (new WriteGameSetSortTitleAction())->execute(
                $gameSet,
                $gameSet->title,
                shouldRespectCustomSortTitle: false,
            );

            $this->info('Done.');
        } else {
            $gameSetsCount = GameSet::where('type', GameSetType::Hub)->count();
            $this->info("\nUpserting sort titles for {$gameSetsCount} hubs.");

            $progressBar = $this->output->createProgressBar($gameSetsCount);
            $progressBar->start();

            $action = new WriteGameSetSortTitleAction();
            GameSet::query()
                ->where('type', GameSetType::Hub)
                ->chunkById(100, function ($gameSets) use ($progressBar, $action) {
                    foreach ($gameSets as $gameSet) {
                        $action->execute($gameSet, $gameSet->title, shouldRespectCustomSortTitle: false);
                    }

                    $progressBar->advance(count($gameSets));
                });

            $progressBar->finish();

            $this->info("\nAll sort titles have been upserted.");
        }
    }
}

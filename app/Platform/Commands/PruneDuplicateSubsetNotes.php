<?php

declare(strict_types=1);

namespace App\Platform\Commands;

use App\Models\GameAchievementSet;
use App\Models\MemoryNote;
use App\Platform\Enums\AchievementSetType;
use Illuminate\Console\Command;

class PruneDuplicateSubsetNotes extends Command
{
    protected $signature = 'ra:platform:prune-duplicate-subset-notes';
    protected $description = "Deletes notes that are identical between the subset and the base set";

    public function handle(): void
    {
        $this->info('Pruning subset notes copied from base sets...');

        $count = 0;

        $bonusAchievementSets = GameAchievementSet::query()
            ->whereIn('type', [AchievementSetType::Bonus, AchievementSetType::WillBeBonus])
            ->get()
            ->mapWithKeys(fn ($i) => [$i->achievement_set_id => $i->game_id]);
        $bonusGameIds = GameAchievementSet::query()
            ->where('type', AchievementSetType::Core)
            ->whereIn('achievement_set_id', $bonusAchievementSets->keys())
            ->get()
            ->mapWithKeys(fn ($i) => [$i->game_id => $bonusAchievementSets[$i->achievement_set_id]]);

        $numNotes = MemoryNote::whereIn('game_id', $bonusGameIds->keys())->count();

        $progressBar = $this->output->createProgressBar($numNotes);
        $progressBar->start();

        foreach ($bonusGameIds as $bonusGameId => $gameId) {
            $subsetNotes = MemoryNote::where('game_id', $bonusGameId)->get();
            $baseNotes = MemoryNote::query()
                ->where('game_id', $gameId)
                ->whereIn('address', $subsetNotes->pluck('address'))
                ->get();

            foreach ($subsetNotes as $subsetNote) {
                $baseNote = $baseNotes->where('address', $subsetNote->address)->first();
                if ($baseNote && trim($baseNote->body) === trim($subsetNote->body)) {
                    $subsetNote->delete();
                    $count++;
                }

                $progressBar->advance();
            }
        }

        $progressBar->finish();
        $this->info("\nPruned {$count} notes.");
    }
}

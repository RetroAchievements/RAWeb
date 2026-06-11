<?php

declare(strict_types=1);

namespace App\Platform\Commands;

use App\Models\Game;
use App\Models\GameBadge;
use App\Models\UserGameBadgePreference;
use App\Platform\Actions\ComputeAchievementsSetPublishedAtAction;
use App\Platform\Enums\GameBadgeAttribution;
use App\Platform\Services\GameBadgeBackfillService;
use Illuminate\Console\Command;

class PruneWipGameBadgesCommand extends Command
{
    protected $signature = 'ra:platform:game-badges:prune-unplayable';
    protected $description = 'Delete backfilled badge rows that were never the current icon while the set was playable.';

    private int $deletedPrePublish = 0;
    private int $deletedNeverPlayable = 0;
    private int $undeterminedGames = 0;

    public function handle(GameBadgeBackfillService $backfillService): void
    {
        $gameIds = GameBadge::query()
            ->where('attribution_source', '!=', GameBadgeAttribution::Live->value)
            ->distinct()
            ->orderBy('game_id')
            ->pluck('game_id');

        $count = $gameIds->count();
        $this->info("Pruning unplayable badges across {$count} games...");

        $progressBar = $this->output->createProgressBar($count);

        foreach ($gameIds->chunk(500) as $chunk) {
            $games = Game::query()->whereIn('id', $chunk)->get()->keyBy('id');

            foreach ($chunk as $gameId) {
                /** @var Game|null $game */
                $game = $games->get($gameId);

                if ($game !== null) {
                    $this->pruneGame($backfillService, $game);
                }

                $progressBar->advance();
            }
        }

        $progressBar->finish();
        $this->newLine();

        $skippedLive = GameBadge::query()
            ->where('attribution_source', GameBadgeAttribution::Live->value)
            ->count();

        $this->info(sprintf(
            'Prune complete. Deleted (pre-publish): %d, deleted (never playable): %d, '
            . 'games kept undetermined: %d, live rows skipped: %d.',
            $this->deletedPrePublish,
            $this->deletedNeverPlayable,
            $this->undeterminedGames,
            $skippedLive,
        ));
    }

    private function pruneGame(GameBadgeBackfillService $backfillService, Game $game): void
    {
        $coreSet = $game->gameAchievementSets()->core()->first()?->achievementSet;

        $firstPublishedAt = $coreSet?->achievements_first_published_at;
        if ($firstPublishedAt === null && $coreSet !== null) {
            $firstPublishedAt = (new ComputeAchievementsSetPublishedAtAction())->execute($coreSet);
        }

        $base = GameBadge::query()
            ->where('game_id', $game->id)
            ->where('attribution_source', '!=', GameBadgeAttribution::Live->value);

        $deleted = 0;

        if ($firstPublishedAt !== null) {
            // a badge whose window ended at or before the first publish was
            // only ever a pre-publish (WIP) badge
            $wipQuery = (clone $base)
                ->whereNotNull('replaced_at')
                ->where('replaced_at', '<=', $firstPublishedAt);

            UserGameBadgePreference::pruneForBadgeRows($wipQuery);

            $deleted = $wipQuery->forceDelete();
            $this->deletedPrePublish += $deleted;
        } elseif (!($game->achievements_published > 0)) {
            // the set was never playable, so none of its badges were ever selectable
            UserGameBadgePreference::pruneForBadgeRows($base);

            $deleted = (clone $base)->forceDelete();
            $this->deletedNeverPlayable += $deleted;
        } else {
            $this->undeterminedGames++;

            return;
        }

        if ($deleted > 0) {
            $backfillService->chainReplacedAtForGame($game->id);
        }
    }
}

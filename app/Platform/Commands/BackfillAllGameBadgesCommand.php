<?php

declare(strict_types=1);

namespace App\Platform\Commands;

use Illuminate\Console\Command;

class BackfillAllGameBadgesCommand extends Command
{
    protected $signature = 'ra:platform:game-badges:backfill-all';
    protected $description = 'Run the full game-badges backfill pipeline in the correct order.';

    /**
     * The order matters. Backfill stuff first, then collapse, then the canonical pass restores
     * each current badge, then prune removes pre-publish/WIP rows. Running these by hand risks
     * sequencing them wrong, so this command is the supported entry point.
     *
     * Re-running is safe, as every step is idempotent.
     *
     * @var list<string>
     */
    private array $steps = [
        'ra:platform:game-badges:backfill-forum-comments',
        'ra:platform:game-badges:backfill-audit-log',
        'ra:platform:game-badges:backfill-comments',
        'ra:platform:game-badges:collapse-same-day',
        'ra:platform:game-badges:backfill-current-canonical',
        'ra:platform:game-badges:prune-unplayable',
    ];

    public function handle(): int
    {
        foreach ($this->steps as $step) {
            $this->info("==> {$step}");

            $code = $this->call($step);

            if ($code !== self::SUCCESS) {
                $this->error("{$step} failed (exit {$code}); aborting.");

                return $code;
            }
        }

        $this->info('game-badges backfill pipeline complete.');

        return self::SUCCESS;
    }
}

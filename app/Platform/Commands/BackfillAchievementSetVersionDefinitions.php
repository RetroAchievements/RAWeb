<?php

declare(strict_types=1);

namespace App\Platform\Commands;

use App\Models\AchievementSetVersion;
use App\Platform\Actions\BuildAchievementSetDefinitionAction;
use Illuminate\Console\Command;

class BackfillAchievementSetVersionDefinitions extends Command
{
    protected $signature = 'ra:platform:achievement-set:backfill-version-definitions
                            {--chunk=200 : Number of latest versions to process per chunk}';
    protected $description = "Snapshot the current achievement definition onto each set's latest version, giving the next revision a baseline to diff against";

    public function handle(): void
    {
        $chunkSize = (int) $this->option('chunk');

        $total = AchievementSetVersion::query()->latestVersion()->count();
        $this->info("Backfilling definitions for {$total} latest achievement set versions.");

        $progressBar = $this->output->createProgressBar($total);
        $progressBar->start();

        $action = new BuildAchievementSetDefinitionAction();

        AchievementSetVersion::query()
            ->latestVersion()
            ->with('achievementSet.achievements')
            ->chunkById($chunkSize, function ($versions) use ($action, $progressBar): void {
                foreach ($versions as $version) {
                    $achievementSet = $version->achievementSet;
                    if ($achievementSet === null) {
                        $progressBar->advance();

                        continue;
                    }

                    $version->definition = $action->execute($achievementSet);
                    $version->save();

                    $progressBar->advance();
                }
            });

        $progressBar->finish();
        $this->newLine();
        $this->info('Backfill complete.');
    }
}

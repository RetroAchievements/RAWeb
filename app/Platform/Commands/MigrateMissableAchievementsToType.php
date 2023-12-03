<?php

declare(strict_types=1);

namespace App\Platform\Commands;

use App\Platform\Enums\AchievementType;
use App\Platform\Models\Achievement;
use Illuminate\Console\Command;

class MigrateMissableAchievementsToType extends Command
{
    protected $signature = "ra:platform:achievement:migrate-missables
                            {achievementId? : Target a single achievement}";
    protected $description = "Migrate '[m]' tagged achievement(s) to the 'missable' type value";

    public function handle(): void
    {
        $achievementId = $this->argument('achievementId');

        if ($achievementId !== null) {
            $achievement = Achievement::findOrFail($achievementId);

            $this->info('Updating missable type for achievement [' . $achievement->id . ']');

            $this->syncMissableTypeForAchievement($achievement);
        } else {
            $allTaggedMissables = Achievement::where('Title', 'like', '%[m]%')->get();

            $this->info('Updating missable types for ' . $allTaggedMissables->count() . ' achievements.');

            foreach ($allTaggedMissables as $achievement) {
                $this->syncMissableTypeForAchievement($achievement);
            }

            $this->info('All achievements have been updated.');
        }
    }

    private function syncMissableTypeForAchievement(Achievement $achievement): void
    {
        $usesLegacyMissableTag = mb_strpos($achievement->Title, '[m]');

        // Is this achievement eligible for syncing? It must contain the legacy
        // missable tag and must not already have a type.
        if ($usesLegacyMissableTag && !$achievement->type) {
            $achievement->type = AchievementType::Missable;

            // Remove the [m] tag and trim whitespace
            $achievement->Title = trim(str_replace('[m]', '', $achievement->Title));
        }

        $achievement->save();
    }
}

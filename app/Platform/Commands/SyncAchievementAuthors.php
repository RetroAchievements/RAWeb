<?php

declare(strict_types=1);

namespace App\Platform\Commands;

use App\Models\Achievement;
use App\Models\User;
use App\Platform\Enums\AchievementAuthorTask;
use Illuminate\Console\Command;

class SyncAchievementAuthors extends Command
{
    protected $signature = 'ra:sync:achievement-authors
                            {achievementId? : Target achievement ID. Leave empty to sync from all achievements.}';
    protected $description = "Write all LOGIC authorship data to achievement_authors";

    public function __construct(
    ) {
        parent::__construct();
    }

    public function handle(): void
    {
        $achievementId = $this->argument('achievementId');

        if ($achievementId) {
            $achievement = Achievement::findOrFail($achievementId);

            $this->syncAchievementLogicAuthors($achievement);
        } else {
            $distinctAchievementIdsCount = Achievement::count();

            $this->info("\nUpserting logic authorship credit for {$distinctAchievementIdsCount} achievements.");

            $progressBar = $this->output->createProgressBar($distinctAchievementIdsCount);
            Achievement::query()->chunk(100, function ($achievements) use ($progressBar) {
                foreach ($achievements as $achievement) {
                    $this->syncAchievementLogicAuthors($achievement, quiet: true);

                    $progressBar->advance();
                }
            });
            $progressBar->finish();

            $this->info("\nCompleted upserting logic authorship credit for {$distinctAchievementIdsCount} achievements.");
        }
    }

    private function syncAchievementLogicAuthors(Achievement $achievement, bool $quiet = false): void
    {
        if (!$quiet) {
            $this->info("\nUpserting logic authorship credit for achievement [{$achievement->id}:{$achievement->title}] ({$achievement->game->title}).");
        }

        $addedAuthors = 0;

        $achievementAuthor = $achievement->developer()->withTrashed()->first();

        if ($achievementAuthor) {
            $achievement->upsertAuthorshipCredit(
                $achievementAuthor,
                AchievementAuthorTask::LOGIC,
                backdate: $achievement->DateCreated,
            );

            $addedAuthors++;
        }

        $systemComments = $achievement->legacyComments()->automated()->get();
        foreach ($systemComments as $systemComment) {
            $payload = $systemComment->Payload;

            // Extract the username from the payload.
            $username = strtok($payload, ' ');

            if (str_contains($payload, 'logic')) {
                $user = User::withTrashed()
                    ->where('User', $username)
                    ->orWhere('display_name', $username)
                    ->first();

                if ($user) {
                    $achievement->upsertAuthorshipCredit(
                        $user,
                        AchievementAuthorTask::LOGIC,
                        backdate: $systemComment->Submitted,
                    );

                    $addedAuthors++;
                }
            }
        }

        if (!$quiet) {
            $this->info("Successfully upserted {$addedAuthors} logic " . ($addedAuthors === 1 ? 'author' : 'authors') . " to [{$achievement->id}:{$achievement->title}] ({$achievement->game->title}).");
        }
    }
}

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
    protected $description = "Write all logic authorship data to achievement_authors";

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

        $creditedAuthorIds = [];

        // Add the currently-assigned author.
        $achievementAuthor = $achievement->developer()->withTrashed()->first();
        if ($achievementAuthor) {
            $achievement->ensureAuthorshipCredit(
                $achievementAuthor,
                AchievementAuthorTask::Logic,
                backdate: $achievement->DateCreated ?? now(),
            );

            $creditedAuthorIds[] = $achievementAuthor->id;
        }

        // Add authors recorded in the comment log.
        $systemComments = $achievement->legacyComments()->automated()->get();
        foreach ($systemComments as $systemComment) {
            $payload = $systemComment->Payload;

            // Extract the username from the payload.
            $words = explode(' ', $payload);
            $username = array_shift($words); // Username is always the first word.

            $expectedPhraseOne = "edited";
            $expectedPhraseTwo = "logic";
            if (in_array($expectedPhraseOne, $words) && in_array($expectedPhraseTwo, $words)) {
                $user = User::withTrashed()
                    ->where('User', $username)
                    ->orWhere('display_name', $username)
                    ->first();

                if ($user && !in_array($user->id, $creditedAuthorIds, true)) {
                    $achievement->ensureAuthorshipCredit(
                        $user,
                        AchievementAuthorTask::Logic,
                        backdate: $systemComment->Submitted,
                    );

                    $creditedAuthorIds[] = $user->id;
                }
            }
        }

        if (!$quiet) {
            $addedAuthors = count($creditedAuthorIds);
            $this->info("Successfully upserted {$addedAuthors} logic " . ($addedAuthors === 1 ? 'author' : 'authors') . " to [{$achievement->id}:{$achievement->title}] ({$achievement->game->title}).");
        }
    }
}

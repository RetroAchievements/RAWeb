<?php

declare(strict_types=1);

namespace App\Platform\Commands;

use App\Models\Achievement;
use App\Models\Game;
use App\Models\Leaderboard;
use App\Models\Trigger;
use App\Platform\Actions\UpsertTriggerVersionAction;
use App\Platform\Enums\AchievementFlag;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SyncTriggers extends Command
{
    protected $signature = 'ra:sync:triggers';
    protected $description = 'Sync memory triggers from achievements, leaderboards, and rich presence.';

    public function handle(): void
    {
        // This will be a full reset. Delete any existing triggers data.
        // We'll use TRUNCATE to reset the auto-incrementing ID counter back to 1.
        $this->info("\nDeleting any existing triggers data...");
        $this->wipeAllTriggersData();
        $this->info("Deleted all existing triggers data.");

        $this->info('Syncing triggers for achievements, leaderboards, and rich presence scripts.');

        $this->syncAchievementTriggers();
        $this->syncLeaderboardTriggers();
        $this->syncRichPresenceTriggers();

        $this->newLine();
        $this->info('Done syncing all triggers.');
    }

    private function syncAchievementTriggers(): void
    {
        $achievementCount = Achievement::whereNotNull('MemAddr')->count();
        $this->info("Syncing triggers for {$achievementCount} achievements...");

        $progressBar = $this->output->createProgressBar($achievementCount);

        Achievement::query()
            ->where('MemAddr', '!=', '')
            ->with(['comments' => function ($query) {
                $query->automated()
                    ->whereRaw("LOWER(Payload) LIKE '% edited%logic%'")
                    ->latest('Submitted')
                    ->limit(1);
            }])
            ->chunk(1000, function ($achievements) use ($progressBar) {
                foreach ($achievements as $achievement) {
                    $newTrigger = (new UpsertTriggerVersionAction())->execute(
                        $achievement,
                        $achievement->MemAddr,
                        versioned: $achievement->Flags === AchievementFlag::OfficialCore->value,
                    );

                    // Try our best to backdate the new trigger's timestamps.
                    if ($newTrigger) {
                        // Get the achievement's creation date for both timestamps.
                        // For the updated timestamp, prefer the latest logic edit comment's timestamp if it exists.
                        // DateModified unfortunately could be all kinds of different things other than logic.
                        $createdTimestamp = $achievement->DateCreated ?? now();
                        $updatedTimestamp = $achievement->comments->first()?->Submitted ?? $createdTimestamp;

                        $newTrigger->timestamps = false;
                        $newTrigger->update([
                            'created_at' => $createdTimestamp,
                            'updated_at' => $updatedTimestamp,
                        ]);
                    }

                    $progressBar->advance();
                }
            });

        $progressBar->finish();
        $this->newLine();
        $this->info('Done syncing triggers for achievements.');
    }

    private function syncLeaderboardTriggers(): void
    {
        $leaderboardCount = Leaderboard::count();
        $this->info("Syncing triggers for {$leaderboardCount} leaderboards...");

        $progressBar = $this->output->createProgressBar($leaderboardCount);

        Leaderboard::query()
            ->with(['comments' => function ($query) {
                $query->automated()
                    ->whereRaw("LOWER(Payload) LIKE '% edited this leaderboard%'")
                    ->latest('Submitted')
                    ->limit(1);
            }])
            ->chunk(1000, function ($leaderboards) use ($progressBar) {
                foreach ($leaderboards as $leaderboard) {
                    $newTrigger = (new UpsertTriggerVersionAction())->execute(
                        $leaderboard,
                        $leaderboard->Mem,
                        versioned: true,
                    );

                    // Try our best to backdate the new trigger's timestamps.
                    if ($newTrigger) {
                        // First determine the creation date.
                        $createdTimestamp =
                            $leaderboard->Created
                            ?? $leaderboard->Updated
                            ?? now();

                        // Then ensure updated is never before created.
                        $updatedTimestamp = max(
                            $createdTimestamp,
                            $leaderboard->comments->first()?->Submitted
                            ?? $leaderboard->Updated
                            ?? $createdTimestamp
                        );

                        $newTrigger->timestamps = false;
                        $newTrigger->update([
                            'created_at' => $createdTimestamp,
                            'updated_at' => $updatedTimestamp,
                        ]);
                    }

                    $progressBar->advance();
                }
            });

        $progressBar->finish();
        $this->newLine();
        $this->info('Done syncing triggers for leaderboards.');
    }

    private function syncRichPresenceTriggers(): void
    {
       $gamesCount = Game::whereNotNull('RichPresencePatch')
           ->where('RichPresencePatch', '!=', '')
           ->count();

       $this->info("Syncing triggers for {$gamesCount} rich presence scripts...");

       $progressBar = $this->output->createProgressBar($gamesCount);

       Game::query()
           ->whereNotNull('RichPresencePatch')
           ->where('RichPresencePatch', '!=', '')
           ->with(['modificationsComments' => function ($query) {
                $query->automated()
                    ->whereRaw("LOWER(Payload) LIKE '%changed the rich presence%'")
                    ->latest('Submitted')
                    ->limit(1);
            }])
           ->chunk(1000, function ($games) use ($progressBar) {
               foreach ($games as $game) {
                   $newTrigger = (new UpsertTriggerVersionAction())->execute(
                       $game,
                       $game->RichPresencePatch,
                       versioned: true
                   );

                    // Try our best to backdate the new trigger's timestamps.
                    if ($newTrigger) {
                        // When RP is edited, a comment is left. Use that comment's submitted
                        // date, otherwise we just have to fall back to the current date.
                        $timestamp = $game->modificationsComments->first()?->Submitted ?? now();

                        $newTrigger->timestamps = false;
                        $newTrigger->update([
                            'created_at' => $timestamp,
                            'updated_at' => $timestamp,
                        ]);
                    }

                   $progressBar->advance();
               }
           });

       $progressBar->finish();
       $this->newLine();
       $this->info('Done syncing triggers for rich presence scripts.');
    }

    private function wipeAllTriggersData(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        Trigger::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }
}

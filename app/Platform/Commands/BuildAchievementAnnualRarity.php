<?php

declare(strict_types=1);

namespace App\Platform\Commands;

use App\Models\Achievement;
use App\Models\GameAchievementSet;
use App\Models\PlayerAchievement;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class BuildAchievementAnnualRarity extends Command
{
    protected $signature = 'ra:platform:achievement:build-annual-rarity';

    protected $description = 'Generates and populates a temporary table with annual unlock rates';

    protected Carbon $newSetThreshold;
    protected array $eligibleGames = [];

    public function handle(): void
    {
        $year = Carbon::now()->subMonths(6)->year;
        $this->newSetThreshold = Carbon::create($year, 12, 15, 0, 0, 0);

        $query = Achievement::promoted()
            ->where('unlocks_total', '>', 0)
            ->select('id', 'game_id', 'created_at')
            ->orderBy('id')
            ->toBase();

        $count = $query->count();
        $this->info("Processing {$count} achievements...");

        if (!Schema::hasTable('temp_achievement_rarity')) {
            Schema::create('temp_achievement_rarity', function (Blueprint $table) {
                $table->id();
                $table->foreignId('achievement_id');
                $table->integer('points_weighted')->default(0);
                $table->unsignedInteger('unlocks_total')->default(0);
                $table->unsignedInteger('unlocks_hardcore')->default(0);
            });
        }

        $progressBar = $this->output->createProgressBar($count);
        $progressBar->start();

        $query->chunk(20, function ($achievements) use ($progressBar) {
            $eligibleAchievementIds = [];
            foreach ($achievements as $achievement) {
                if ($this->isGameEligible($achievement->game_id) && $this->newSetThreshold->gt($achievement->created_at)) {
                    $eligibleAchievementIds[] = $achievement->id;
                }
            }

            if (!empty($eligibleAchievementIds)) {
                $this->process($eligibleAchievementIds);
            }

            $progressBar->advance(20);
        });

        $progressBar->finish();
        $this->newLine();
    }

    public function isGameEligible(int $gameId): bool
    {
        if (!array_key_exists($gameId, $this->eligibleGames)) {
            $achievementSet = GameAchievementSet::where('game_id', $gameId)->core()->first()->achievementSet;

            $this->eligibleGames[$gameId] = $achievementSet->players_total >= 10
                || $this->newSetThreshold->gt($achievementSet->achievements_first_published_at);
        }

        return $this->eligibleGames[$gameId];
    }

    public function process(array $achievementIds): void
    {
        // intentionally not filtering out untracked users as they still receive annual recap
        // emails and excluding them might generate a "you earned an achievement earned by 0 people".
        // it also complicates an already-expensive query.
        DB::table('temp_achievement_rarity')->insertUsing(
            ['achievement_id', 'points_weighted', 'unlocks_total', 'unlocks_hardcore'],
            PlayerAchievement::join('achievements', 'achievements.id', '=', 'player_achievements.achievement_id')
                ->whereIn('player_achievements.achievement_id', $achievementIds)
                ->where('unlocked_effective_at', '>=', "{$this->newSetThreshold->year}-01-01")
                ->select(
                    'achievements.id as achievement_id',
                    'achievements.points_weighted as points_weighted',
                    DB::raw("count(*) as unlocks_total"),
                    DB::raw("sum(if(unlocked_hardcore_at is not null, 1, 0)) as unlocks_hardcore"),
                )
                ->groupBy('player_achievements.achievement_id')
        );
    }
}

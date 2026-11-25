<?php

declare(strict_types=1);

namespace App\Platform\Commands;

use App\Models\Achievement;
use App\Models\PlayerAchievement;
use App\Platform\Enums\AchievementSetType;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class BuildAchievementAnnualRarity extends Command
{
    protected $signature = 'ra:platform:achievement:build-annual-rarity';

    protected $description = 'Generates and populates a temporary table with annual unlock rates';

    protected array $eligibleGames = [];

    public function handle(): void
    {
        $year = Carbon::now()->subMonths(6)->year;

        $start = Carbon::create($year, 1, 1);
        $end = Carbon::create($year + 1, 1, 1);

        $this->info('Fetching total unlock counts');
        $totals = PlayerAchievement::query()
            ->where('unlocked_effective_at', '>=', $start)
            ->where('unlocked_effective_at', '<', $end)
            ->groupBy('achievement_id')
            ->pluck(DB::raw('count(*)'), 'achievement_id');

        $this->info('Fetching hardcore unlock counts');
        $hardcore = PlayerAchievement::query()
            ->where('unlocked_hardcore_at', '>=', $start)
            ->where('unlocked_hardcore_at', '<', $end)
            ->groupBy('achievement_id')
            ->pluck(DB::raw('count(*)'), 'achievement_id');

        $this->info('Fetching eligible achievements');
        // Ignore any sets published in the last two weeks as low unlock
        // rates on them may be due to their freshness.
        $newSetThreshold = Carbon::create($year, 12, 15);
        $eligibleIds = Achievement::promoted()
            ->where('achievements.created_at', '<', $newSetThreshold)
            ->join('game_achievement_sets', fn ($join) => $join
                ->on('game_achievement_sets.game_id', '=', 'achievements.game_id')
                ->where('game_achievement_sets.type', AchievementSetType::Core))
            ->join('achievement_sets', 'achievement_sets.id', '=', 'game_achievement_sets.achievement_set_id')
            ->where(fn ($q) => $q
                ->where('achievement_sets.players_total', '>=', 10)
                ->orWhere('achievement_sets.achievements_first_published_at', '<', $newSetThreshold))
            ->pluck('achievements.points_weighted', 'achievements.id');

        $rows = $eligibleIds
            ->filter(fn ($points_weighted, $id) => isset($totals[$id]))
            ->map(fn ($points_weighted, $id) => [
                'achievement_id' => $id,
                'points_weighted' => $points_weighted,
                'unlocks_total' => $totals[$id],
                'unlocks_hardcore' => $hardcore[$id] ?? 0,
            ]);

        $count = count($rows);
        $this->info("Populating $count rows in temp table");
        $progressBar = $this->output->createProgressBar($count);
        $progressBar->start();

        Schema::dropIfExists('temp_achievement_rarity');
        Schema::create('temp_achievement_rarity', function (Blueprint $table) {
            $table->id();
            $table->foreignId('achievement_id')->index();
            $table->integer('points_weighted')->default(0);
            $table->unsignedInteger('unlocks_total')->default(0);
            $table->unsignedInteger('unlocks_hardcore')->default(0);
        });

        foreach ($rows->chunk(5000) as $batch) {
            DB::table('temp_achievement_rarity')->insert($batch->values()->all());
            $progressBar->advance(5000);
        }

        $progressBar->finish();
        $this->newLine();
    }
}

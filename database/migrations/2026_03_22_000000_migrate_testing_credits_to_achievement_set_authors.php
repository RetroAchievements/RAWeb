<?php

declare(strict_types=1);

use App\Models\AchievementAuthor;
use App\Models\AchievementSetAuthor;
use App\Platform\Enums\AchievementSetAuthorTask;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Move testing credits from achievement-level to achievement-set-level.
        // Groups by (user_id, achievement_set_id), keeping the earliest credit date.
        $groupedCredits = AchievementAuthor::query()
            ->join('achievement_set_achievements', 'achievement_set_achievements.achievement_id', '=', 'achievement_authors.achievement_id')
            ->where('achievement_authors.task', 'testing')
            ->select(
                'achievement_authors.user_id',
                'achievement_set_achievements.achievement_set_id',
                DB::raw('MIN(achievement_authors.created_at) as earliest_created_at'),
            )
            ->groupBy('achievement_authors.user_id', 'achievement_set_achievements.achievement_set_id')
            ->get();

        foreach ($groupedCredits as $credit) {
            AchievementSetAuthor::firstOrCreate(
                [
                    'user_id' => $credit->user_id,
                    'achievement_set_id' => $credit->achievement_set_id,
                    'task' => AchievementSetAuthorTask::Testing,
                ],
                [
                    'created_at' => $credit->earliest_created_at,
                ],
            );
        }

        // Soft-delete the migrated achievement-level testing credits.
        AchievementAuthor::where('task', 'testing')->delete();
    }

    public function down(): void
    {
        // Restore the soft-deleted achievement-level testing credits.
        AchievementAuthor::withTrashed()
            ->where('task', 'testing')
            ->whereNotNull('deleted_at')
            ->restore();

        // Remove the migrated set-level testing credits.
        AchievementSetAuthor::where('task', AchievementSetAuthorTask::Testing)
            ->forceDelete();
    }
};

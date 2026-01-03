<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Community\Enums\ClaimStatus;
use App\Enums\Permissions;
use App\Models\AchievementSet;
use App\Models\AchievementSetClaim;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class AchievementSetClaimSeeder extends Seeder
{
    public function run(): void
    {
        if (AchievementSetClaim::count() > 0) {
            return;
        }

        // completed claims
        AchievementSet::query()
            ->where('achievements_published', '>', 0)
            ->orderByDesc('achievements_first_published_at')
            ->limit(60)
            ->get()->each(function (AchievementSet $set) {
                AchievementSetClaim::factory()->create([
                    'game_id' => $set->games()->first()->id,
                    'user_id' => $set->achievements()->first()->user_id,
                    'created_at' => $set->achievements_first_published_at->clone()->subDays(rand(2, 50))->subMinutes(rand(1, 600)),
                    'finished_at' => $set->achievements_first_published_at->clone()->addMinutes(rand(1, 100)),
                    'status' => ClaimStatus::Complete,
                ]);
            });

        // active claims
        $count = rand(10, 25);
        AchievementSet::query()
            ->where('achievements_published', 0)
            ->inRandomOrder()
            ->get()->each(function (AchievementSet $set) use (&$count) {
                $game = $set->games()->first();
                if (!isValidConsoleId($game->system_id)) {
                    return;
                }

                if ($count === 0) {
                    return;
                }
                $count--;

                $user = User::where('Permissions', '>=', Permissions::JuniorDeveloper)->inRandomOrder()->first();
                $claimStart = Carbon::now()->clone()->subDays(rand(2, 100))->subMinutes(rand(1, 600));
                AchievementSetClaim::factory()->create([
                    'game_id' => $game->id,
                    'user_id' => $user->id,
                    'created_at' => $claimStart,
                    'finished_at' => $claimStart->clone()->addMonths(3),
                    'status' => ClaimStatus::Active,
                ]);

                // TODO: generate some InReview claims with Unofficial achievements
            });
    }
}

<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Community\Enums\ClaimStatus;
use App\Community\Models\AchievementSetClaim;
use App\Models\Game;
use Illuminate\Database\Seeder;

class AchievementSetClaimSeeder extends Seeder
{
    public function run(): void
    {
        if (AchievementSetClaim::count() > 0) {
            return;
        }

        Game::take(5)->get()->each(function (Game $game) {
            AchievementSetClaim::factory()->count(5)->create([
                'GameID' => $game->ID,
                'Status' => ClaimStatus::Complete,
            ]);
        });
    }
}

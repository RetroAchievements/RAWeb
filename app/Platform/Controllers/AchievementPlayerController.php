<?php

declare(strict_types=1);

namespace App\Platform\Controllers;

use App\Http\Controller;
use App\Platform\Models\Achievement;
use App\Platform\Models\PlayerAchievement;
use Illuminate\Contracts\View\View;

class AchievementPlayerController extends Controller
{
    protected function resourceName(): string
    {
        return 'achievement.player';
    }

    public function index(Achievement $achievement): View
    {
        $this->authorize('view', $achievement);

        $achievement->loadMissing([
            'game',
        ]);

        $unlocks = $achievement->hasMany(PlayerAchievement::class);
        $numWinners = $unlocks->count();
        $numPossibleWinners = $achievement->game()->first()->players()->count();

        return view('achievement.player.index', [
                'numWinners' => $numWinners,
                'numPossibleWinners' => $numPossibleWinners,
                'winnerPercent' => round($numWinners * 100 / $numPossibleWinners, 2),
            ])
            ->with('achievement', $achievement);
    }
}

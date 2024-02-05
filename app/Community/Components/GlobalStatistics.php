<?php

declare(strict_types=1);

namespace App\Community\Components;

use App\Models\Game;
use App\Models\StaticData;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Illuminate\View\Component;

class GlobalStatistics extends Component
{
    private ?StaticData $dbStaticData;

    public function __construct()
    {
        $this->dbStaticData = StaticData::with('lastRegisteredUser')->first();
    }

    public function render(): ?View
    {
        if ($this->dbStaticData === null) {
            return null;
        }

        $globalStatisticsViewValues = $this->buildAllGlobalStatisticsViewValues(
            $this->dbStaticData
        );

        return view('components.global-statistics.global-statistics', $globalStatisticsViewValues);
    }

    private function buildAllGlobalStatisticsViewValues(StaticData $staticData): array
    {
        $numGames = $staticData['NumGames'];
        $numAchievements = $staticData['NumAchievements'];
        $numAwarded = $staticData['NumAwarded'];
        $numRegisteredPlayers = $staticData['NumRegisteredUsers'];
        $numHardcoreGameBeatenAwards = $staticData['num_hardcore_game_beaten_awards'];
        $numHardcoreMasteryAwards = $staticData['num_hardcore_mastery_awards'];
        $totalPointsEarned = $staticData['TotalPointsEarned'];

        $lastMasteredGameId = $staticData['last_game_hardcore_mastered_game_id'];
        $lastMasteredUserId = $staticData['last_game_hardcore_mastered_user_id'];
        $lastMasteredTimestamp = $staticData['last_game_hardcore_mastered_at'];
        $lastMasteredTimeAgo = Carbon::parse($lastMasteredTimestamp)->diffForHumans();

        $lastBeatenGameId = $staticData['last_game_hardcore_beaten_game_id'];
        $lastBeatenUserId = $staticData['last_game_hardcore_beaten_user_id'];
        $lastBeatenTimestamp = $staticData['last_game_hardcore_beaten_at'];
        $lastBeatenTimeAgo = Carbon::parse($lastBeatenTimestamp)->diffForHumans();

        $lastRegisteredUser = $staticData['LastRegisteredUser'];
        if ($lastRegisteredUser == null) {
            $lastRegisteredUser = 'unknown';
        }

        $lastRegisteredUserAt = $staticData['LastRegisteredUserAt'];
        if ($lastRegisteredUserAt) {
            $lastRegisteredUserTimeAgo = Carbon::createFromFormat("Y-m-d H:i:s", $lastRegisteredUserAt)->diffForHumans();
        } else {
            $lastRegisteredUserTimeAgo = null;
        }

        $lastMasteredGame = Game::find($lastMasteredGameId);
        $lastBeatenGame = Game::find($lastBeatenGameId);

        return compact(
            'lastBeatenUserId',
            'lastBeatenTimeAgo',
            'lastMasteredTimeAgo',
            'lastMasteredUserId',
            'lastRegisteredUser',
            'lastRegisteredUserTimeAgo',
            'numAchievements',
            'numAwarded',
            'numGames',
            'numHardcoreGameBeatenAwards',
            'numHardcoreMasteryAwards',
            'numRegisteredPlayers',
            'totalPointsEarned',
            'lastMasteredGame',
            'lastBeatenGame',
        );
    }
}

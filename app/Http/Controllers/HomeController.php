<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Controller;
use App\Models\StaticData;
use App\Platform\Models\Achievement;
use App\Platform\Models\Game;
use App\Platform\Models\System;
use Illuminate\Contracts\View\View;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        $currentEventMetadata = $this->fetchCurrentEventMetadata();

        if ($currentEventMetadata === null) {
            return view('pages.index');
        }

        return view('pages.index', $currentEventMetadata);
    }

    // TODO: This should eventually support multiple different kinds of events.
    private function fetchCurrentEventMetadata(): ?array
    {
        $staticData = StaticData::first();

        if ($staticData === null) {
            return null;
        }

        $aotwAchievementId = $staticData['Event_AOTW_AchievementID'];
        $eventForumTopicId = $staticData['Event_AOTW_ForumID'];
        $achievement = Achievement::find($aotwAchievementId);

        if (!$achievement) {
            return null;
        }

        $game = Game::find($achievement->GameID);
        $consoleName = System::find($game->ConsoleID)->Name;

        return [
            'eventAchievement' => $achievement,
            'eventGame' => $game,
            'eventConsoleName' => $consoleName,
            'eventForumTopicId' => $eventForumTopicId,
        ];
    }
}

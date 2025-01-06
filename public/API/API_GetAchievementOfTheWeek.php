<?php

use App\Http\Actions\BuildAchievementOfTheWeekDataAction;
use App\Models\Achievement;
use App\Models\EventAchievement;
use App\Models\StaticData;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/*
 *  API_GetAchievementOfTheWeek
 *   (no inputs)
 *
 *  object     Achievement              information about the achievement
 *   int        ID                      unique identifier of the achievement
 *   string     Title                   title of the achievement
 *   string     Description             description of the achievement
 *   int        Points                  number of points the achievement is worth
 *   int        TrueRatio               number of RetroPoints ("white points") the achievement is worth
 *   string     Type                    null, "progression", "win_condition", or "missable"
 *   string     Author                  user who first created the achievement
 *   string     BadgeName               unique identifier of the badge image for the achievement
 *   string     BadgeURL                site-relative path to the badge image for the achievement
 *   datetime   DateCreated             when the achievement was created
 *   datetime   DateModified            when the achievement was last modified
 *  object     Console                  information about the console associated to the game associated to the achievement
 *   int        ID                      unique identifier of the console
 *   string     Title                   name of the console
 *  object     Game                     information about the game associated to the achievement
 *   int        ID                      unique identifier of the game
 *   string     Title                   name of the game
 *  object     ForumTopic               information about the game's official forum topic
 *   int        ID                      unique identifier of the game's official forum topic
 *  datetime   StartAt                  when the achievement was set as the achievement of the week
 *  int        UnlocksCount             number of times the achievement has been unlocked
 *  int        UnlocksHardcoreCount     number of times the achievement has been unlocked in hardcore mode
 *  int        TotalPlayers             number of players who have played the game associated to the achievement
 *  array      Unlocks                  requested unlock information
 *   string     User                    user who unlocked the achievement
 *   int        RAPoints                number of points the user has
 *   int        RASoftcorePoints        number of softcore points the user has
 *   datetime   DateAwarded             when the achievement was unlocked
 *   int        HardcoreMode            1 if unlocked in hardcore, otherwise 0
 */

/*
 * NOTE: this is just a copy of API_GetAchievementUnlocks that is hardcoded to query the
 *       achievement of the week data. It adds StartAt and ForumTopic to the output and
 *       filters the Unlocks to just those entries after StartAt.
 */

$staticData = StaticData::first();
$aotwData = (new BuildAchievementOfTheWeekDataAction())->execute($staticData);

$achievementId = $aotwData->achievement->id ?? 0;
$eventAchievement = EventAchievement::active()->where('achievement_id', $achievementId)->first();

$sourceAchievement = $eventAchievement ? $eventAchievement->sourceAchievement : Achievement::find($achievementId);
if (!$sourceAchievement) {
    return response()->json([
        'Achievement' => ['ID' => null],
        'StartAt' => null,
    ]);
}

$achievement = [
    'ID' => $sourceAchievement->ID ?? null,
    'Title' => $sourceAchievement->Title ?? null,
    'Description' => $sourceAchievement->Description ?? null,
    'Points' => $sourceAchievement->Points ?? null,
    'TrueRatio' => $sourceAchievement->TrueRatio ?? null,
    'Type' => $sourceAchievement->type ?? null,
    'Author' => $sourceAchievement->author->display_name ?? null,
    'BadgeName' => $sourceAchievement->BadgeName,
    'BadgeURL' => "/Badge/" . $sourceAchievement->BadgeName . ".png",
    'DateCreated' => $sourceAchievement->DateCreated?->format('Y-m-d'),
    'DateModified' => $sourceAchievement->DateModified?->format('Y-m-d'),
];

$game = [
    'ID' => $sourceAchievement->game->id,
    'Title' => $sourceAchievement->game->title,
];

$console = [
    'ID' => $sourceAchievement->game->system->id,
    'Title' => $sourceAchievement->game->system->name,
];

$forumTopic = [
    'ID' => $aotwData->forumTopicId->resolve() ?? null,
];

if ($eventAchievement) {
    $unlocks = collect();

    $playerAchievements = $eventAchievement->achievement->playerAchievements()
        ->with('user')
        ->orderBy(DB::raw('IFNULL(unlocked_hardcore_at, unlocked_at)'))
        ->limit(500)
        ->get();
    $numWinners = $playerAchievements->count();
    $numWinnersHardcore = 0;

    foreach ($playerAchievements as $playerAchievement) {
        $unlocks[] = [
            'User' => $playerAchievement->user->display_name,
            'RAPoints' => $playerAchievement->user->RAPoints,
            'RASoftcorePoints' => $playerAchievement->user->RASoftcorePoints,
            'HardcoreMode' => $playerAchievement->unlocked_hardcore_at !== null ? 1 : 0,
            'DateAwarded' => $playerAchievement->unlocked_hardcore_at ?? $playerAchievement->unlocked_at,
        ];

        if ($playerAchievement->unlocked_hardcore_at !== null) {
            $numWinnersHardcore++;
        }
    }

    $numPossibleWinners = $eventAchievement->achievement->game->players_total;
    $startAt = $eventAchievement->active_from;

} else {
    $parentGame = getParentGameFromGameTitle($game['Title'], $console['ID']);
    $unlocks = getAchievementUnlocksData($achievementId, null, $numWinners, $numWinnersHardcore, $numPossibleWinners, $parentGame?->id, 0, 500);

    /*
    * reset unlocks if there is no start date to prevent listing invalid entries
    */
    $startAt = $staticData->Event_AOTW_StartAt;
    if (empty($startAt)) {
        $unlocks = collect();
    }

    if (!empty($startAt)) {
        $unlocks = $unlocks->filter(fn ($unlock) => Carbon::parse($unlock['DateAwarded'])->gte($startAt));
    }

    // reverse order so newest winners are last
    $unlocks->sortByDesc('DateAwarded');
}

return response()->json([
    'Achievement' => $achievement,
    'Console' => $console,
    'ForumTopic' => $forumTopic,
    'Game' => $game,
    'StartAt' => $startAt,
    'TotalPlayers' => $numPossibleWinners ?? 0,
    'Unlocks' => $unlocks->values(),
    'UnlocksCount' => $numWinners ?? 0,
    'UnlocksHardcoreCount' => $numWinnersHardcore ?? 0,
]);

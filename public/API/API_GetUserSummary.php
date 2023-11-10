<?php

/*
 *  API_GetUserSummary
 *    u : username
 *    g : number of recent games to return (default: 5)
 *    a : number of recent achievements to return (default: 10)
 *
 *  int        ID                      unique identifier of the user
 *  int        TotalPoints             number of hardcore points the user has
 *  int        TotalSoftcorePoints     number of softcore points the user has
 *  int        TotalTruePoints         number of "white" points the user has
 *  int        Permissions             unique identifier of user's account type
 *  datetime   MemberSince             when the user joined the site
 *  int?       Rank                    user's site rank
 *  int        Untracked               "1" if the user is untracked, otherwise "0"
 *  string     UserPic                 site-relative path to the user's profile picture
 *  string     Motto                   the user's motto
 *  int        UserWallActive          "1" if the user allows comments to be posted to their wall, otherwise "0"
 *  int        TotalRanked             total number of ranked users
 *  int        LastGameID              unique identifier of the last game the user played
 *  object     LastGame                information about the last game the user played
 *   int        ID                     unique identifier of the game
 *   string     Title                  name of the game
 *   int        ConsoleID              unique identifier of the console associated to the game
 *   string     ConsoleName            name of the console associated to the game
 *   int        ForumTopicID           unique identifier of the official forum topic for the game
 *   int        Flags                  always "0"
 *   string     ImageIcon              site-relative path to the game's icon image
 *   string     ImageTitle             site-relative path to the game's title image
 *   string     ImageIngame            site-relative path to the game's in-game image
 *   string     ImageBoxArt            site-relative path to the game's box art image
 *   string     Publisher              publisher information for the game
 *   string     Developer              developer information for the game
 *   string     Genre                  genre information for the game
 *   string     Released               release date information for the game
 *   int        IsFinal
 *  string     RichPresenceMsg         activity information about the last game the user played
 *  int        RecentlyPlayedCount     number of items in the RecentlyPlayed array
 *  array      RecentlyPlayed
 *   int        GameID                 unique identifier of the game
 *   string     Title                  name of the game
 *   int        ConsoleID              unique identifier of the console associated to the game
 *   string     ConsoleName            name of the console associated to the game
 *   string     ImageIcon              site-relative path to te game's icon
 *   datetime   LastPlayed             when the user last played the game
 *  object     LastActivity
 *   int        ID                     unique identifier of the activity
 *   datetime   timestamp              when the activity occurred
 *   datetime   lastupdate             when the activity was last modified
 *   int        activitytype           the type of activity
 *   string     User                   the user associated to the activity
 *   string     data                   additional information about the activity
 *   string     data2                  additional information about the activity
 *  string     Status                  "Offline" if the last activity is more than 10 minute ago, otherwise "Online"
 *  map        Awarded
 *   string     [key]                  unique identifier of the game
 *    int        NumAchieved           count of Core achievements unlocked by the user
 *    int        NumAchievedHardcore   count of Core achievements unlocked by the user in Hardcore mode
 *    int        NumPossibleAchievements count of Core achievements for the game
 *    string     ScoreAchieved         total points earned by the user from the game
 *    string     ScoreAchievedHardcore points earned by the user from the game for playing in Hardcore mode
 *    string     PossibleScore         maximum points attainable from the game
 *  map        RecentAchievements
 *   string     [key]                  unique identifier of the game
 *    string     [key]                 unique identifier of the achievement
 *     int        ID                   unique identifier of the achievement
 *     string     Title                title of the achievement
 *     string     Description          description of the achievement
 *     int        Points               number of points the achievement is worth
 *     string     BadgeName            unique identifier of the badge image for the achievement
 *     int        GameID               unique identifier of the game
 *     string     GameTitle            name of the game
 *     string     IsAwarded            always "1"
 *     datetime   DateAwarded          when the user earned the achievement
 *     int?       HardcoreAchieved     1 for hardcore award, 0 for non-hardcore award
 *  int        ContribCount            achievements won by others
 *  int        ContribYield            points awarded to others
 */

use App\Support\Rules\CtypeAlnum;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;

$input = Validator::validate(Arr::wrap(request()->query()), [
    'u' => ['required', 'min:2', 'max:20', new CtypeAlnum()],
    'g' => 'nullable|integer|min:0',
    'a' => 'nullable|integer|min:0',
]);

$user = request()->query('u');
$recentGamesPlayed = (int) request()->query('g', '5');
$recentAchievementsEarned = (int) request()->query('a', '10');

$retVal = getUserPageInfo($user, $recentGamesPlayed, $recentAchievementsEarned);

if (empty($retVal)) {
    return response()->json([
        'ID' => null,
        'User' => $user,
    ], 404);
}

$retVal['UserPic'] = "/UserPic/" . $user . ".png";
$retVal['TotalRanked'] = countRankedUsers();

// assume caller doesn't care about the rich presence script for the last game played
if (array_key_exists('LastGame', $retVal)) {
    unset($retVal['LastGame']['RichPresencePatch']);
    unset($retVal['LastGame']['system']);
}

$retVal['LastActivity'] = [
    'ID' => 0,
    'timestamp' => null,
    'lastupdate' => null,
    'activitytype' => null,
    'User' => $user,
    'data' => null,
    'data2' => null,
];
unset($retVal['LastActivityID']);

$retVal['Status'] = 'Offline';

return response()->json($retVal);

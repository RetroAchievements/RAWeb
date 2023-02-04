<?php

/*
 *  API_GetUserSummary
 *    u : username
 *    g : number of recent games to return (default: 5)
 *    a : number of recent achievements to return (default: 10)
 *
 *  string     ID                      unique identifier of the user
 *  string     TotalPoints             number of hardcore points the user has
 *  string     TotalSoftcorePoints     number of softcore points the user has
 *  string     TotalTruePoints         number of "white" points the user has
 *  string     Permissions             unique identifier of user's account type
 *  datetime   MemberSince             when the user joined the site
 *  int?       Rank                    user's site rank
 *  string     Untracked               "1" if the user is untracked, otherwise "0"
 *  string     UserPic                 site-relative path to the user's profile picture
 *  string     Motto                   the user's motto
 *  string     UserWallActive          "1" if the user allows comments to be posted to their wall, otherwise "0"
 *  string     TotalRanked             total number of ranked users
 *  string     LastGameID              unique identifier of the last game the user played
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
 *   bool       IsFinal
 *   string     RichPresencePatch      script for generating the rich presence for the game
 *  string     RichPresenceMsg         activity information about the last game the user played
 *  int        RecentlyPlayedCount     number of items in the RecentlyPlayed array
 *  array      RecentlyPlayed
 *   string     GameID                 unique identifier of the game
 *   string     Title                  name of the game
 *   string     ConsoleID              unique identifier of the console associated to the game
 *   string     ConsoleName            name of the console associated to the game
 *   string     ImageIcon              site-relative path to te game's icon
 *   datetime   LastPlayed             when the user last played the game
 *   string     MyVote                 the user's rating of the game
 *  object     LastActivity
 *   string     ID                     unique identifier of the activity
 *   datetime   timestamp              when the activity occurred
 *   datetime   lastupdate             when the activity was last modified
 *   string     activitytype           the type of activity
 *   string     User                   the user associated to the activity
 *   string     data                   additional information about the activity
 *   string     data2                  additional information about the activity
 *  string     Status                  "Offline" if the last activity is more than 10 minute ago, otherwise "Online"
 *  map        Awarded
 *   string     [key]                  unique identifier of the game
 *    string     NumAchieved           count of Core achievements unlocked by the user
 *    string     NumAchievedHardcore   count of Core achievements unlocked by the user in Hardcore mode
 *    string     NumPossibleAchievements count of Core achievements for the game
 *    string     ScoreAchieved         points earned by the user from the game
 *    string     ScoreAchievedHardcore additional points earned by the user from the game for playing in Hardcore mode
 *    string     PossibleScore         maximum points attainable from the game
 *  map        RecentAchievements
 *   string     [key]                  unique identifier of the game
 *    string     [key]                 unique identifier of the achievement
 *     string     ID                   unique identifier of the achievement
 *     string     Title                title of the achievement
 *     string     Description          description of the achievement
 *     string     Points               number of points the achievement is worth
 *     string     BadgeName            unique identifier of the badge image for the achievement
 *     string     GameID               unique identifier of the game
 *     string     GameTitle            name of the game
 *     string     IsAwarded            always "1"
 *     datetime   DateAwarded          when the user earned the achievement
 *     string     HardcoreAchieved     always "0"?
 *  string     ContribCount            achievements won by others
 *  string     ContribYield            points awarded to others
 */

$user = request()->query('u');
$recentGamesPlayed = (int) request()->query('g', '5');
$recentAchievementsEarned = (int) request()->query('a', '10');

$retVal = [];
getUserPageInfo($user, $retVal, $recentGamesPlayed, $recentAchievementsEarned, null);

if (!$retVal) {
    return response()->json([
        'ID' => null,
        'User' => $user,
    ], 404);
}

getAccountDetails($user, $userDetails);

$retVal['ID'] = $userDetails['ID'];
$retVal['Points'] = $userDetails['RAPoints'];
$retVal['SoftcorePoints'] = $userDetails['RASoftcorePoints'];
$retVal['Motto'] = $userDetails['Motto'];
$retVal['UserPic'] = "/UserPic/" . $user . ".png";
$retVal['Rank'] = getUserRank($user);
$retVal['TotalRanked'] = countRankedUsers();

// Find out if we're online or offline
$retVal['LastActivity'] = getActivityMetadata($userDetails['LastActivityID']);

$status = 'Offline';
if ($retVal['LastActivity']) {
    $lastUpdate = (int) date("U", strtotime($retVal['LastActivity']['lastupdate']));
    $now = (int) date("U");
    $status = ($lastUpdate + 600) > $now ? 'Online' : 'Offline';
}
$retVal['Status'] = $status;

return response()->json($retVal);

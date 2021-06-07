<?php

namespace RA;

class RetroAchievementsWebApiClient
{
    public const API_URL = 'https://retroachievements.org/API/';

    public $ra_user;

    public $ra_api_key;

    public function __construct($user, $api_key)
    {
        $this->ra_user = $user;
        $this->ra_api_key = $api_key;
    }

    private function AuthQS()
    {
        return "?z=" . $this->ra_user . "&y=" . $this->ra_api_key;
    }

    private function GetRAURL($target, $params = "")
    {
        return file_get_contents(self::API_URL . $target . self::AuthQS() . "&$params");
    }

    public function GetTopTenUsers()
    {
        return json_decode(self::GetRAURL('API_GetTopTenUsers.php'));
    }

    public function GetGameInfo($gameID)
    {
        return json_decode(self::GetRAURL("API_GetGame.php", "i=$gameID"));
    }

    public function GetGameInfoExtended($gameID)
    {
        return json_decode(self::GetRAURL("API_GetGameExtended.php", "i=$gameID"));
    }

    public function GetConsoleIDs()
    {
        return json_decode(self::GetRAURL('API_GetConsoleIDs.php'));
    }

    public function GetGameList($consoleID)
    {
        return json_decode(self::GetRAURL("API_GetGameList.php", "i=$consoleID"));
    }

    public function GetFeedFor($user, $count, $offset = 0)
    {
        return json_decode(self::GetRAURL("API_GetFeed.php", "u=$user&c=$count&o=$offset"));
    }

    public function GetUserRankAndScore($user)
    {
        return json_decode(self::GetRAURL("API_GetUserRankAndScore.php", "u=$user"));
    }

    public function GetUserProgress($user, $gameIDCSV)
    {
        $gameIDCSV = preg_replace('/\s+/', '', $gameIDCSV);    //	Remove all whitespace
        return json_decode(self::GetRAURL("API_GetUserProgress.php", "u=$user&i=$gameIDCSV"));
    }

    public function GetUserRecentlyPlayedGames($user, $count, $offset = 0)
    {
        return json_decode(self::GetRAURL("API_GetUserRecentlyPlayedGames.php", "u=$user&c=$count&o=$offset"));
    }

    public function GetUserSummary($user, $numRecentGames)
    {
        return json_decode(self::GetRAURL("API_GetUserSummary.php", "u=$user&g=$numRecentGames&a=5"));
    }

    public function GetGameInfoAndUserProgress($user, $gameID)
    {
        return json_decode(self::GetRAURL("API_GetGameInfoAndUserProgress.php", "u=$user&g=$gameID"));
    }

    public function GetAchievementsEarnedOnDay($user, $dateInput)
    {
        return json_decode(self::GetRAURL("API_GetAchievementsEarnedOnDay.php", "u=$user&d=$dateInput"));
    }

    public function GetAchievementsEarnedBetween($user, $dateStart, $dateEnd)
    {
        $dateFrom = strtotime($dateStart);
        $dateTo = strtotime($dateEnd);
        return json_decode(self::GetRAURL("API_GetAchievementsEarnedBetween.php", "u=$user&f=$dateFrom&t=$dateTo"));
    }

    public function GetUserGamesCompleted($user)
    {
        return json_decode(self::GetRAURL("API_GetUserCompletedGames.php", "u=$user"));
    }
}

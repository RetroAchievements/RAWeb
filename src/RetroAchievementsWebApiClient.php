<?php

namespace RA;

class RetroAchievementsWebApiClient
{
    public const API_URL = 'https://retroachievements.org/API/';

    public function __construct(
        public $ra_user,
        public $ra_api_key,
    ) {
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
        return json_decode(self::GetRAURL('API_GetTopTenUsers.php'), null, 512, JSON_THROW_ON_ERROR);
    }

    public function GetGameInfo($gameID)
    {
        return json_decode(self::GetRAURL("API_GetGame.php", "i=$gameID"), null, 512, JSON_THROW_ON_ERROR);
    }

    public function GetGameInfoExtended($gameID)
    {
        return json_decode(self::GetRAURL("API_GetGameExtended.php", "i=$gameID"), null, 512, JSON_THROW_ON_ERROR);
    }

    public function GetConsoleIDs()
    {
        return json_decode(self::GetRAURL('API_GetConsoleIDs.php'), null, 512, JSON_THROW_ON_ERROR);
    }

    public function GetGameList($consoleID)
    {
        return json_decode(self::GetRAURL("API_GetGameList.php", "i=$consoleID"), null, 512, JSON_THROW_ON_ERROR);
    }

    public function GetFeedFor($user, $count, $offset = 0)
    {
        return json_decode(self::GetRAURL("API_GetFeed.php", "u=$user&c=$count&o=$offset"), null, 512, JSON_THROW_ON_ERROR);
    }

    public function GetUserRankAndScore($user)
    {
        return json_decode(self::GetRAURL("API_GetUserRankAndScore.php", "u=$user"), null, 512, JSON_THROW_ON_ERROR);
    }

    public function GetUserProgress($user, $gameIDCSV)
    {
        $gameIDCSV = preg_replace('/\s+/', '', $gameIDCSV);    // Remove all whitespace
        return json_decode(self::GetRAURL("API_GetUserProgress.php", "u=$user&i=$gameIDCSV"), null, 512, JSON_THROW_ON_ERROR);
    }

    public function GetUserRecentlyPlayedGames($user, $count, $offset = 0)
    {
        return json_decode(self::GetRAURL("API_GetUserRecentlyPlayedGames.php", "u=$user&c=$count&o=$offset"), null, 512, JSON_THROW_ON_ERROR);
    }

    public function GetUserSummary($user, $numRecentGames)
    {
        return json_decode(self::GetRAURL("API_GetUserSummary.php", "u=$user&g=$numRecentGames&a=5"), null, 512, JSON_THROW_ON_ERROR);
    }

    public function GetGameInfoAndUserProgress($user, $gameID)
    {
        return json_decode(self::GetRAURL("API_GetGameInfoAndUserProgress.php", "u=$user&g=$gameID"), null, 512, JSON_THROW_ON_ERROR);
    }

    public function GetAchievementsEarnedOnDay($user, $dateInput)
    {
        return json_decode(self::GetRAURL("API_GetAchievementsEarnedOnDay.php", "u=$user&d=$dateInput"), null, 512, JSON_THROW_ON_ERROR);
    }

    public function GetAchievementsEarnedBetween($user, $dateStart, $dateEnd)
    {
        $dateFrom = strtotime($dateStart);
        $dateTo = strtotime($dateEnd);
        return json_decode(self::GetRAURL("API_GetAchievementsEarnedBetween.php", "u=$user&f=$dateFrom&t=$dateTo"), null, 512, JSON_THROW_ON_ERROR);
    }

    public function GetUserGamesCompleted($user)
    {
        return json_decode(self::GetRAURL("API_GetUserCompletedGames.php", "u=$user"), null, 512, JSON_THROW_ON_ERROR);
    }
}

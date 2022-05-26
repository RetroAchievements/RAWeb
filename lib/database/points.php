<?php

function recalculateTrueRatio($gameID): bool
{
    sanitize_sql_inputs($gameID);

    return (new TrueRetroRatio())->Recalculate($gameID);

    /*
     * PREVIOUS VERSION
     */
    // $query = "SELECT ach.ID, ach.Points, COUNT(*) AS NumAchieved
    //           FROM Achievements AS ach
    //           LEFT JOIN Awarded AS aw ON aw.AchievementID = ach.ID
    //           LEFT JOIN UserAccounts AS ua ON ua.User = aw.User
    //           WHERE ach.GameID = $gameID AND ach.Flags = 3 AND (aw.HardcoreMode = 1 OR aw.HardcoreMode IS NULL)
    //           AND (NOT ua.Untracked OR ua.Untracked IS NULL)
    //           GROUP BY ach.ID";
    //
    // $dbResult = s_mysql_query($query);
    //
    // if ($dbResult !== false) {
    //     $numHardcoreWinners = getTotalUniquePlayers($gameID, null, true, 3);
    //
    //     if ($numHardcoreWinners == 0) { // force all unachieved to be 1
    //         $numHardcoreWinners = 1;
    //     }
    //
    //     $ratioTotal = 0;
    //     while ($nextData = mysqli_fetch_assoc($dbResult)) {
    //         $achID = $nextData['ID'];
    //         $achPoints = (int) $nextData['Points'];
    //         $numAchieved = (int) $nextData['NumAchieved'];
    //
    //         if ($numAchieved == 0) { // force all unachieved to be 1
    //             $numAchieved = 1;
    //         }
    //
    //         $ratioFactor = 0.4;
    //         $newTrueRatio = ($achPoints * (1.0 - $ratioFactor)) + ($achPoints * (($numHardcoreWinners / $numAchieved) * $ratioFactor));
    //         $trueRatio = (int) $newTrueRatio;
    //         $ratioTotal += $trueRatio;
    //
    //         $query = "UPDATE Achievements AS ach
    //                   SET ach.TrueRatio = $trueRatio
    //                   WHERE ach.ID = $achID";
    //         s_mysql_query($query);
    //     }
    //
    //     $query = "UPDATE GameData AS gd
    //               SET gd.TotalTruePoints = $ratioTotal
    //               WHERE gd.ID = $gameID";
    //     s_mysql_query($query);
    //
    //     // RECALCULATED " . count($achData) . " achievements for game ID $gameID ($ratioTotal)"
    //
    //     return true;
    // } else {
    //     return false;
    // }
}

function getDbData($query)
{
    $result = s_mysql_query($query);
    if (!$result) {
        return null;
    }

    $data = null;
    while ($nextData = mysqli_fetch_object($result)) {
        $data[] = $nextData;
    }
    return $data;
}

function getTheMostCommonAchievement($gameID, $hardcore)
{
    if (!is_numeric($gameID) || !is_numeric($hardcore)) {
        return null;
    }
    // not the same as "uniquePlayers" - always lower, sometimes even 1.5 times
    $query = "SELECT ach.ID as ID, COUNT(*) AS Achieved
        FROM Achievements AS ach 
        LEFT JOIN Awarded AS aw ON aw.AchievementID = ach.ID 
        WHERE ach.GameID = $gameID AND ach.Flags = 3 AND aw.HardcoreMode = $hardcore
        GROUP BY ach.ID ORDER BY `Achieved` DESC LIMIT 1";
    $data = getDbData($query);
    return ($data == null) ? null : $data[0];
}

function getNumUniquePlayers($gameID, $hardcore, $checkUnTracked = false)
{
    // isn't this value should be saved in the 'gamedata' table instead of this ugliness?
    // check for untracked slow down the request in 30+ times (but affects Retropoints <0.5%)

    if (!is_numeric($gameID) || !is_numeric($hardcore)) {
        return null;
    }

    $query = "SELECT COUNT(DISTINCT aw.User) As UniquePlayers
    FROM Awarded AS aw
    LEFT JOIN Achievements AS ach ON ach.ID = aw.AchievementID
    LEFT JOIN GameData AS gd ON gd.ID = ach.GameID";
    if ($checkUnTracked) {
        $query = $query . " LEFT JOIN UserAccounts AS ua ON ua.User = aw.User";
    }
    $query = $query . " WHERE gd.ID = $gameID AND aw.HardcoreMode = $hardcore AND ach.Flags = 3";

    if ($checkUnTracked) {
        $query = $query . " AND NOT ua.Untracked";
    }
    $data = getDbData($query);
    return ($data == null) ? null : $data[0]->UniquePlayers;
}

class TrueRetroRatio_WorkAroundV1
{
    // /this spaghetti-class should be replaced with a function in V2

    private function getGameNameAndConsole($gameID)
    {
        $query = "SELECT Title, ConsoleID as Console FROM GameData WHERE ID = $gameID";
        $data = getDbData($query);
        return ($data == null) ? null : $data[0];
    }

    private function getAchsForGame($gameID)
    {
        $query = "SELECT ach.ID, ach.Points, ach.DateCreated
              FROM Achievements AS ach
              WHERE ach.GameID = $gameID AND ach.Flags = 3
              ORDER BY ach.DateCreated ASC";
        $data = getDbData($query);
        return ($data == null) ? null : $data;
    }

    private function getAchieved($gameID, $hardcore)
    {
        // Check for untracked? Slow...
        $query = "SELECT ach.ID, COUNT(*) AS Achieved
              FROM Achievements AS ach
              LEFT JOIN Awarded AS aw ON aw.AchievementID = ach.ID
              WHERE ach.GameID = $gameID AND ach.Flags = 3 AND aw.HardcoreMode = $hardcore
              GROUP BY ach.ID, ach.DateCreated ORDER BY ach.DateCreated ASC";
        $data = getDbData($query);
        return ($data == null) ? null : $data;
    }

    private function getParentIdForBonus($gameInfo)
    {
        $realName = mb_substr($gameInfo->Title, 8);
        $query = "SELECT ID FROM GameData WHERE Title = '$realName' and ConsoleID = $gameInfo->Console";
        $data = getDbData($query);
        return ($data == null) ? null : $data[0]->ID;
    }

    private function getAchieversForRevisions($achievementsGrouped, $achievementID)
    {
        $subQuery = "";
        for ($i = 1; $i < count($achievementsGrouped); $i++) {
            $dc = $achievementsGrouped[$i]->achievements[0]->DateCreated;
            $subQueryPart = "COUNT(IF(Date >= '$dc', 1, NULL)) AS 'a$i'";
            $subQuery = ($i > 1 ? "$subQuery, " : "") . $subQueryPart;
        }

        $query = "SELECT $subQuery
        FROM awarded WHERE AchievementID = $achievementID AND HardcoreMode = 1 ORDER BY Date ASC";
        // I also tried the simple
        // "SELECT Date FROM awarded WHERE AchievementID = $achievementID AND HardcoreMode = 1 ORDER BY Date ASC"
        // and then calculating groups from PHP - the execution speed is exactly the same
        $data = getDbData($query);
        return ($data == null) ? null : $data[0];
    }

    private function constructAchGroup($achievements = [], $players = 0)
    {
        return (object) [
            'achievements' => $achievements,
            'players' => $players,
        ];
    }

    private function groupAchievements($achsNotGrouped, $bonus)
    {
        $revisionThrDays = 60;
        $revisionThr = $revisionThrDays * 86400;
        // not all days have 86400 secs but we don't need that much accuracy

        $achsGroupedByRevision = $bonus
            ? [TrueRetroRatio_WorkAroundV1::constructAchGroup()]
            : [];
        $revisionGroup = [];

        $dateGroupStart = strtotime($achsNotGrouped[0]->DateCreated);
        foreach ($achsNotGrouped as $ach) {
            $count = count($revisionGroup);
            $dateCreated = strtotime($ach->DateCreated);
            if ($count > 0 && ($dateCreated - $dateGroupStart > $revisionThr)) {
                $dateGroupStart = $dateCreated;
                $achsGroupedByRevision[] =
                    TrueRetroRatio_WorkAroundV1::constructAchGroup($revisionGroup);
                $revisionGroup = [];
            }
            $revisionGroup[] = $ach;
        }
        $achsGroupedByRevision[] = TrueRetroRatio_WorkAroundV1::constructAchGroup($revisionGroup);
        return $achsGroupedByRevision;
    }

    private function getTheMostCommonAchievement($gotParent, $gameID, $hardcore, $firstGroup)
    {
        if ($gotParent) {
            return getTheMostCommonAchievement($gameID, $hardcore);
            // 8300: 0.15-0.17s, 5000: 0.08, 3500: 0.06, 2000: 0.04, 500: 0.005
        }
        // the version below is almost instantaneous
        $theMostCommonAchievement = (object) [
            'ID' => 0,
            'Achieved' => 0,
        ];
        foreach ($firstGroup as $ach) {
            if ($ach->Achieved > $theMostCommonAchievement->Achieved) {
                $theMostCommonAchievement->Achieved = $ach->Achieved;
                $theMostCommonAchievement->ID = $ach->ID;
            }
        }

        return $theMostCommonAchievement;
    }

    private function calculatePlayersPerRevision($achsGrouped, $gameID, $parent, $HC, $checkUnTrThr)
    {
        // this function looks strange because of all changes during optimizations
        $theCommonestAchievement = null;

        $theCommonestAchievement = TrueRetroRatio_WorkAroundV1::getTheMostCommonAchievement($parent, $gameID, $HC, $achsGrouped[0]->achievements);
        if ($theCommonestAchievement == null) {
            return null;
        }

        $checkUnTr = $theCommonestAchievement->Achieved < $checkUnTrThr;
        $gamePlayers = (int) getNumUniquePlayers($gameID, $HC, $checkUnTr);
        // checkUnTracked true  - 8300: 6.4-7.9s 5000: 3.7s, 3500: 3.0s, 2000: 1.6s, 1000: 0.4s, 500: 0.2s
        // checkUnTracked false - 8300: 0.2s     5000: 0.1s, 3500: 0.03, 2000: 0.02

        if ($gamePlayers == null) {
            return null;
        }
        $achsGrouped[0]->players = (int) $parent ? round($gamePlayers / 4) : $gamePlayers;

        $count = count($achsGrouped);
        if ($count < 2) {
            return $achsGrouped;
        }

        $achievers = TrueRetroRatio_WorkAroundV1::getAchieversForRevisions(
            $achsGrouped,
            $theCommonestAchievement->ID
        );
        // 8300: 1.1s, 5000: 0.7s, 3500: 0.5s, 2000: 0.3s, 500: 0.07 sec

        $achievers0 = (int) ($theCommonestAchievement->Achieved);

        for ($i = 1; $i < $count; $i++) {
            $iS = "a$i";
            $percent = $achievers->$iS / $achievers0;
            $players = (int) round($percent * $achsGrouped[0]->players);
            $achsGrouped[$i]->players = $players;
        }

        return $achsGrouped;
    }

    public function getAchievementsGroupedByRevision($gameID, $HC, $checkUnTrThr)
    {
        $gameInfo = TrueRetroRatio_WorkAroundV1::getGameNameAndConsole($gameID);
        if ($gameInfo == null) {
            return null;
        }

        $bonus = mb_substr($gameInfo->Title, 0, 3) === "~Bo";

        $achsNotGrouped = TrueRetroRatio_WorkAroundV1::getAchsForGame($gameID);
        if ($achsNotGrouped == null) {
            return null;
        }
        $achieved = TrueRetroRatio_WorkAroundV1::getAchieved($gameID, $HC);
        if (empty($achieved)) {
            return null;
        }
        // getAchieved doesn't return achievements with 0 players
        // Don't know if it's possible to do with 1 sql request
        foreach ($achsNotGrouped as $ach) {
            $ach->Achieved = 0;
            foreach ($achieved as $a) {
                if ($ach->ID == $a->ID) {
                    $ach->Achieved = (int) $a->Achieved;
                    break;
                }
            }
        }

        $achsGrouped = TrueRetroRatio_WorkAroundV1::groupAchievements($achsNotGrouped, $bonus);

        $realID = null;
        if ($bonus) {
            $realID = TrueRetroRatio_WorkAroundV1::getParentIdForBonus($gameInfo);
            // returns null if not found
        }
        $gotParent = $realID != null;
        if (!$gotParent) {
            $realID = $gameID;
        }

        return TrueRetroRatio_WorkAroundV1::calculatePlayersPerRevision($achsGrouped, $realID, $gotParent, $HC, $checkUnTrThr);
    }
}

class TrueRetroRatio
{
    private function getAchievementsGroupedByRevision($gameID, $hardcore)
    {
        // Here will be a normal code with 1-2 requests in V2 instead of this

        $checkUnTrackedThr = 500;
        // for popular game with not checking them the score changes only by ~0.5% max but speedup a lot
        // Game          SMW  DKC3   SM3   SoR  SotN  R-Type
        // HardcPlayers 8300  5000  3500  2000  1000   500
        // Check UnTr.   11s  4.6s  3.6s  1.9s  0.6s   0.3s
        // !Check UnTr. 1.2s  0.9s  0.6s  0.4s  0.2s  <0.1s

        return (new TrueRetroRatio_WorkAroundV1())->getAchievementsGroupedByRevision($gameID, $hardcore, $checkUnTrackedThr);
    }

    private function boostRevisions($achsGrouped)
    {
        // Because old players will start earn achievements immediately
        if (count($achsGrouped) < 2) {
            return $achsGrouped;
        }
        $minRevPlayers = (int) round(
            ($achsGrouped[0]->players < 100)
                ? 0.2 * $achsGrouped[0]->players
                : 2 * sqrt($achsGrouped[0]->players)
        );
        for ($i = 1; $i < count($achsGrouped); $i++) {
            $achsGrouped[$i]->players = $achsGrouped[$i]->players + $minRevPlayers;
        }

        return $achsGrouped;
    }

    private function getPercentageOfBoost($players)
    {
        $boost0At = 1000;
        if ($players >= $boost0At) {
            return 0;
        }

        // $boost50At = 200;
        // $magicPower = -log10(2)/log10(1 + log10($boost50At / $boost0At) / 2);
        // equal to LOG(0.5, 1 + LOG($boost50At / $boost0At, 100)) but PHP doesn't allow that format
        // defines at what percent of players the boost will be 50% of the power;
        $magicPower = 1.6120042064746;
        // this is saved result for 50% of boost at 20% of players of boost0 (200/1000)

        return 1 - (log10($players / $boost0At * 100) / 2) ** $magicPower;
        // if trying to explain this as simple as possible:
        // it's similar to 1/x with controllable descending speed and it reaches 0
        // Just check the graph: https://cdn.discordapp.com/attachments/590225863690289162/743771816023162951/unknown.png
    }

    private function updateAchievements($achsGrouped)
    {
        $boostUnpopularMaxPower = 0.6;
        // (1+this) = multiplier at 0 players for 0% achievement
        // this is NOT "total RP" multiplier, it depends on an achievement percentage and players

        $ratioFactor = 0.4;
        $ratioFactorBase = 1.0 - $ratioFactor;

        $totalPoints = 0;
        foreach ($achsGrouped as $group) {
            $players = $group->players > 0 ? $group->players : 1;
            $boost = $boostUnpopularMaxPower * TrueRetroRatio::getPercentageOfBoost($players);
            foreach ($group->achievements as $ach) {
                $percentage = ($ach->Achieved < 1 ? 0.5 : $ach->Achieved) / $players;
                $retroRatio = ($ratioFactorBase + $ratioFactor / $percentage);
                if ($retroRatio < 1) {
                    $retroRatio = 1;
                }
                if ($boost > 0) {
                    $retroRatio = ($retroRatio - 1) * ($boost + 1) + 1;
                    // this equals to $percentage = $percentage  / (1 + $boost * (1 - $percentage));
                }
                $retroPoints = (int) round(($ach->Points * $retroRatio));
                $totalPoints += $retroPoints;
                $query = "UPDATE Achievements AS ach
                  SET ach.TrueRatio = $retroPoints
                  WHERE ach.ID = $ach->ID";
                s_mysql_query($query);
            }
        }

        return $totalPoints;
    }

    private function updateGame($gameID, $retroPoints)
    {
        $query = "UPDATE GameData AS gd
              SET gd.TotalTruePoints = $retroPoints
              WHERE gd.ID = $gameID";
        s_mysql_query($query);
    }

    public function Recalculate($gameID, $hardcore = 1)
    {
        if (!is_numeric($gameID)) {
            return false;
        }
        $achievementsGrouped = TrueRetroRatio::getAchievementsGroupedByRevision($gameID, $hardcore);
        if (empty($achievementsGrouped)) {
            return false;
        }

        $achievementsGrouped = TrueRetroRatio::boostRevisions($achievementsGrouped);
        $totalPoints = TrueRetroRatio::updateAchievements($achievementsGrouped);
        TrueRetroRatio::updateGame($gameID, $totalPoints);
        return true;
    }
}

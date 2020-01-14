<?php

function getGameRating($gameID)
{
    settype($gameID, 'integer');
    $query = "SELECT r.RatingObjectType, SUM(r.RatingValue)/COUNT(r.RatingValue) AS AvgPct, COUNT(r.RatingValue) AS NumVotes
              FROM Rating AS r
              WHERE r.RatingID = $gameID
              GROUP BY r.RatingObjectType";

    // log_sql($query);
    global $db;
    $dbResult = mysqli_query($db, $query);    //    NB. query has a forward slash in! Cannot use s_mysql_query
    SQL_ASSERT($dbResult);

    $retVal = [];
    while ($nextRow = mysqli_fetch_array($dbResult)) {
        $retVal[$nextRow['RatingObjectType']] = $nextRow;
    }

    return $retVal;
}

function submitGameRating($user, $ratingType, $ratingID, $ratingValue)
{
    settype($ratingType, 'integer');
    settype($ratingID, 'integer');
    settype($ratingValue, 'integer');

    $query = "INSERT INTO Rating ( User, RatingObjectType, RatingID, RatingValue )
              VALUES( '$user', $ratingType, $ratingID, $ratingValue )
              ON DUPLICATE KEY UPDATE RatingValue=VALUES(RatingValue)";

    // log_sql($query);

    $dbResult = s_mysql_query($query);
    return $dbResult !== false;
}

function getGamesByRating($offset, $count)
{
    $query = "SELECT gd.ID AS GameID, gd.Title AS GameTitle, gd.ImageIcon AS GameIcon, c.Name AS ConsoleName, AVG(RatingValue) AS AvgVote, COUNT(RatingID) AS NumVotes
FROM Rating 
LEFT JOIN GameData gd ON gd.ID = RatingID
LEFT JOIN Console c ON c.ID = gd.ConsoleID
WHERE RatingObjectType=1 
GROUP BY RatingID 
ORDER BY AvgVote DESC, NumVotes DESC
LIMIT $offset, $count";

    $dbResult = s_mysql_query($query);

    $retVal = [];
    while ($nextRow = mysqli_fetch_array($dbResult)) {
        $retVal[] = $nextRow;
    }

    return $retVal;
}

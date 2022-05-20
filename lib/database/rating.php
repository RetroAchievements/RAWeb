<?php

use RA\ObjectType;

function getGameRating($gameID, $user = null): array
{
    $newRatings = function () {
        return [
            'RatingCount' => 0,
            'Rating1' => 0,
            'Rating2' => 0,
            'Rating3' => 0,
            'Rating4' => 0,
            'Rating5' => 0,
        ];
    };

    $retVal = [];
    $retVal[ObjectType::Game] = $newRatings();
    $retVal[ObjectType::Achievement] = $newRatings();

    sanitize_sql_inputs($gameID);
    settype($gameID, 'integer');
    $query = "SELECT r.RatingObjectType, r.RatingValue, COUNT(r.RatingValue) AS NumVotes
              FROM Rating AS r
              WHERE r.RatingID = $gameID
              GROUP BY r.RatingObjectType, r.RatingValue";

    global $db;
    $dbResult = mysqli_query($db, $query);

    while ($nextRow = mysqli_fetch_array($dbResult)) {
        $type = $nextRow['RatingObjectType'];
        $retVal[$type]['RatingCount'] += $nextRow['NumVotes'];
        $retVal[$type]['Rating' . $nextRow['RatingValue']] += $nextRow['NumVotes'];
    }

    foreach ($retVal as &$ratingData) {
        if ($ratingData['RatingCount'] == 0) {
            $ratingData['AverageRating'] = 0.0;
        } else {
            $ratingData['AverageRating'] =
                floatval($ratingData['Rating1'] * 1 +
                         $ratingData['Rating2'] * 2 +
                         $ratingData['Rating3'] * 3 +
                         $ratingData['Rating4'] * 4 +
                         $ratingData['Rating5'] * 5) / $ratingData['RatingCount'];
        }
    }

    if (!empty($user)) {
        foreach ($retVal as &$ratingData) {
            $ratingData['UserRating'] = 0;
        }

        sanitize_sql_inputs($user);
        $query = "SELECT RatingObjectType, RatingValue FROM Rating WHERE RatingID=$gameID AND User='$user'";
        $dbResult = mysqli_query($db, $query);

        while ($nextRow = mysqli_fetch_array($dbResult)) {
            $type = $nextRow['RatingObjectType'];
            $retVal[$type]['UserRating'] = $nextRow['RatingValue'];
        }
    }

    return $retVal;
}

function submitGameRating($user, $ratingType, $ratingID, $ratingValue): bool
{
    sanitize_sql_inputs($user, $ratingType, $ratingID, $ratingValue);
    settype($ratingType, 'integer');
    settype($ratingID, 'integer');
    settype($ratingValue, 'integer');

    $query = "INSERT INTO Rating ( User, RatingObjectType, RatingID, RatingValue )
              VALUES( '$user', $ratingType, $ratingID, $ratingValue )
              ON DUPLICATE KEY UPDATE RatingValue=VALUES(RatingValue)";

    $dbResult = s_mysql_query($query);
    return $dbResult !== false;
}

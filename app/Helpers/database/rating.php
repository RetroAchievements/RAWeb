<?php

use App\Community\Enums\RatingType;

function getGameRating(int $gameID, ?string $user = null): array
{
    $newRatings = fn () => [
        'RatingCount' => 0,
        'Rating1' => 0,
        'Rating2' => 0,
        'Rating3' => 0,
        'Rating4' => 0,
        'Rating5' => 0,
    ];

    $retVal = [];
    $retVal[RatingType::Game] = $newRatings();
    $retVal[RatingType::Achievement] = $newRatings();

    $query = "SELECT r.RatingObjectType, r.RatingValue, COUNT(r.RatingValue) AS NumVotes
              FROM Rating AS r
              WHERE r.RatingID = $gameID
              GROUP BY r.RatingObjectType, r.RatingValue";

    foreach (legacyDbFetchAll($query) as $nextRow) {
        $type = $nextRow['RatingObjectType'];
        $retVal[$type]['RatingCount'] += $nextRow['NumVotes'];
        $retVal[$type]['Rating' . $nextRow['RatingValue']] += $nextRow['NumVotes'];
    }

    foreach ($retVal as &$ratingData) {
        if ($ratingData['RatingCount'] == 0) {
            $ratingData['AverageRating'] = 0.0;
        } else {
            $ratingData['AverageRating'] =
                (float) ($ratingData['Rating1'] * 1 +
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

        $query = "SELECT RatingObjectType, RatingValue FROM Rating WHERE RatingID=$gameID AND User=:username";
        foreach (legacyDbFetchAll($query, ['username' => $user]) as $nextRow) {
            $type = $nextRow['RatingObjectType'];
            $retVal[$type]['UserRating'] = $nextRow['RatingValue'];
        }
    }

    return $retVal;
}

function submitGameRating(string $user, int $ratingType, int $ratingID, int $ratingValue): bool
{
    sanitize_sql_inputs($user);

    $query = "INSERT INTO Rating ( User, RatingObjectType, RatingID, RatingValue )
              VALUES( '$user', $ratingType, $ratingID, $ratingValue )
              ON DUPLICATE KEY UPDATE RatingValue=VALUES(RatingValue)";

    $dbResult = s_mysql_query($query);

    return $dbResult !== false;
}

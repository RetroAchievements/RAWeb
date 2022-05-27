<?php

use RA\AwardType;

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../lib/bootstrap.php';

// TODO do not allow GET requests, POST only
if (ValidatePOSTChars("tdev")) {
    $awardType = requestInputPost('t', null, 'integer');
    $awardData = requestInputPost('d', null, 'integer');
    $awardDataExtra = requestInputPost('e', null, 'integer');
    $value = requestInputPost('v', null, 'integer');
} else {
    if (ValidateGETChars("tdev")) {
        $awardType = requestInputQuery('t', null, 'integer');
        $awardData = requestInputQuery('d', null, 'integer');
        $awardDataExtra = requestInputQuery('e', null, 'integer');
        $value = requestInputQuery('v', null, 'integer');
    } else {
        echo "FAILED";
        exit;
    }
}

if (!authenticateFromCookie($user, $permissions, $userDetails)) {
    echo "FAILED!";
    exit;
}

/**
 * change display order for all entries if it's a "stacking" award type
 */
if (in_array($awardType, [AwardType::AchievementUnlocksYield, AwardType::AchievementPointsYield])) {
    $query = "UPDATE SiteAwards SET DisplayOrder = $value WHERE User = '$user' " .
        "AND AwardType = $awardType " .
        "AND AwardDataExtra = $awardDataExtra";
} else {
    $query = "UPDATE SiteAwards SET DisplayOrder = $value WHERE User = '$user' " .
        "AND AwardType = $awardType " .
        "AND AwardData = $awardData";
}

if (s_mysql_query($query)) {
    echo "OK";
} else {
    echo "FAILED!";
}

<?php

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../lib/bootstrap.php';

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
        // error_log("FAILED access to requestupdatesiteaward.php");
        echo "FAILED";
        return;
    }
}

if (!RA_ReadCookieCredentials($user, $points, $truePoints, $unreadMessageCount, $permissions)) {
    echo "FAILED!";
    return;
}

/**
 * change display order for all entries if it's a "stacking" award type
 */
if (in_array($awardType, [2, 3])) {
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
    // error_log("requestupdatesiteaward.php failed?! 1" . var_dump($_POST));
    echo "FAILED!";
}

<?php

require_once __DIR__ . '/../../../vendor/autoload.php';

if (ValidatePOSTChars("tdev")) {
    $awardType = seekPOST('t');
    $awardData = seekPOST('d');
    $awardDataExtra = seekPOST('e');
    $value = seekPOST('v');
} else {
    if (ValidateGETChars("tdev")) {
        $awardType = seekGET('t');
        $awardData = seekGET('d');
        $awardDataExtra = seekGET('e');
        $value = seekGET('v');
    } else {
        // error_log("FAILED access to requestupdatesiteaward.php");
        echo "FAILED";
        return;
    }
}

settype($awardType, "integer");
settype($awardData, "integer");
settype($awardDataExtra, "integer");
settype($value, "integer");

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

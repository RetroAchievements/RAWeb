<?php
require_once __DIR__ . '/../../lib/bootstrap.php';
if (ValidatePOSTChars("utdev")) {
    $user = seekPOST('u');
    $awardType = seekPOST('t');
    $awardData = seekPOST('d');
    $awardDataExtra = seekPOST('e');
    $value = seekPOST('v');
} else {
    if (ValidateGETChars("utdev")) {
        $user = seekGET('u');
        $awardType = seekGET('t');
        $awardData = seekGET('d');
        $awardDataExtra = seekGET('e');
        $value = seekGET('v');
    } else {
        error_log("FAILED access to requestupdatesiteaward.php");
        echo "FAILED";
        return;
    }
}

settype($user, "string");
settype($awardType, "integer");
settype($awardData, "integer");
settype($awardDataExtra, "integer");
settype($value, "integer");

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
        "AND AwardData = $awardData " .
        "AND AwardDataExtra = $awardDataExtra";
}

if (s_mysql_query($query)) {
    echo "OK";
} else {
    error_log("requestupdatesiteaward.php failed?! 1" . var_dump($_POST));
    echo "FAILED!";
}

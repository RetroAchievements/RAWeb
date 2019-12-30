<?php
require_once __DIR__ . '/../../lib/bootstrap.php';

if (ValidatePOSTChars("uafv")) {
    $user = seekPOST('u');
    $achID = seekPOST('a');
    $field = seekPOST('f');
    $value = seekPOST('v');
} else {
    if (ValidateGETChars("uafv")) {
        $user = seekGET('u');
        $achID = seekGET('a');
        $field = seekGET('f');
        $value = seekGET('v');
    } else {
        error_log("FAILED access to requestupdateachievements.php");
        echo "FAILED";
        return;
    }
}

if (!validateFromCookie($user, $points, $permissions, \RA\Permissions::Developer)) {
    echo "FAILED! Unauthenticaed";
    return;
}

settype($achID, "integer");
settype($field, "integer");

error_log("Warning: $user changing achievement ID $achID, field $field");

switch ($field) {
    case 1:
        // display order
        settype($value, "integer");
        if (updateAchievementDisplayID($achID, $value)) {
            echo "OK";
            return;
        }
        error_log("requestupdateachievement.php failed?! 1" . var_dump($_POST));
        echo "FAILED!";
        break;
    case 2:
        // Embed video
        $value = str_replace("_http_", "http", $value);
        if (updateAchievementEmbedVideo($achID, $value)) {
            //header( "Location: " . getenv('APP_URL') . "/Achievement/$achID?e=OK" );
            echo "OK";
            return;
        }
        error_log("requestupdateachievement.php failed?! 2" . var_dump($_POST));
        echo "FAILED!";
        break;
    case 3:
        // Flags
        $validFlags = [3, 5];
        settype($value, "integer");
        if (!in_array($value, $validFlags)) {
            echo "FAILED!";
        }
        if (updateAchievementFlags($achID, $value)) {
            header("Location: " . getenv('APP_URL') . "/Achievement/$achID?e=changeok");
            if ($value == 3) {
                $commentText = 'promoted this achievement to the Core set';
            }
            if ($value == 5) {
                $commentText = 'demoted this achievement to Unofficial';
            }
            addArticleComment("Server", 2, $achID, "\"$user\" $commentText.", $user);
        } else {
            error_log("requestupdateachievement.php failed?! 3" . var_dump($_POST));
            echo "FAILED!";
        }
        break;
    default:
        error_log("requestupdateachievement.php failed?!" . var_dump($_POST));
        echo "FAILED!";
        break;
}

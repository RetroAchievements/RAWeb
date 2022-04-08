<?php

use RA\ArticleType;
use RA\Permissions;

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../lib/bootstrap.php';

if (ValidatePOSTChars("uafv")) {
    $user = requestInputPost('u');
    $achID = requestInputPost('a', null, 'integer');
    $field = requestInputPost('f', null, 'integer');
    $value = requestInputPost('v');
} else {
    if (ValidateGETChars("uafv")) {
        $user = requestInputQuery('u');
        $achID = requestInputQuery('a', null, 'integer');
        $field = requestInputQuery('f', null, 'integer');
        $value = requestInputQuery('v');
    } else {
        echo "FAILED";
        exit;
    }
}

if (!validateFromCookie($user, $points, $permissions, Permissions::JuniorDeveloper)) {
    echo "FAILED! Unauthenticaed";
    exit;
}

// Only allow jr. devs to update the display order and they are the sole author of the set
if ($permissions == Permissions::JuniorDeveloper) {
    $jrDevAllowed = false;
    if ($field == 1) {
        if (ValidatePOSTChars("g")) {
            $gameID = requestInputPost('g', null, 'integer');
        } else {
            if (ValidateGETChars("g")) {
                $gameID = requestInputQuery('g', null, 'integer');
            } else {
                echo "FAILED";
                exit;
            }
        }
        $jrDevAllowed = checkIfSoleDeveloper($user, $gameID);
    }

    if (!$jrDevAllowed) {
        echo "FAILED! Insufficient permissions";
        exit;
    }
}

$commentText = null;
switch ($field) {
    case 1:
        // display order
        settype($value, "integer");
        if (updateAchievementDisplayID($achID, $value)) {
            echo "OK";
            exit;
        }
        echo "FAILED!";
        break;
    case 2:
        // Embed video
        $value = str_replace("_http_", "http", $value);
        if (updateAchievementEmbedVideo($achID, $value)) {
            // header( "Location: " . getenv('APP_URL') . "/achievement/$achID?e=OK" );
            echo "OK";
            exit;
        }
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
            header("Location: " . getenv('APP_URL') . "/achievement/$achID?e=changeok");
            if ($value == 3) {
                $commentText = 'promoted this achievement to the Core set';
            }
            if ($value == 5) {
                $commentText = 'demoted this achievement to Unofficial';
            }
            addArticleComment("Server", ArticleType::Achievement, $achID, "\"$user\" $commentText.", $user);
        } else {
            echo "FAILED!";
        }
        break;
    case 4:
        // Promote/Demote Selected
        settype($value, "integer");
        $achIDs = requestInputPost('achievementArray');
        if (updateAchievementFlags($achIDs, $value)) {
            if ($value == 3) {
                $commentText = 'promoted this achievement to the Core set';
            }
            if ($value == 5) {
                $commentText = 'demoted this achievement to Unofficial';
            }
            addArticleComment("Server", ArticleType::Achievement, $achIDs, "\"$user\" $commentText.", $user);
        } else {
            echo "FAILED!";
        }
        break;
    default:
        echo "FAILED!";
        break;
}

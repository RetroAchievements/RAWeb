<?php

use RA\AchievementType;
use RA\ArticleType;
use RA\Permissions;

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../lib/bootstrap.php';

if (!ValidatePOSTChars("uafv")) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Bad request: parameters missing']);
    exit;
}

$user = requestInputPost('u');
$achievementId = requestInputPost('a'); // can be array or integer als string. cast where needed
$field = requestInputPost('f', null, 'integer');
$value = requestInputPost('v');

if (!authenticateFromCookie($user, $permissions, $userDetails, Permissions::JuniorDeveloper)) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

// Only allow jr. devs to update the display order or achievements if they are the sole author of the set
if ($permissions == Permissions::JuniorDeveloper) {
    $jrDevAllowed = false;
    if ($field == 1) {
        if (ValidatePOSTChars("g")) {
            $gameID = requestInputPost('g', null, 'integer');
        } else {
            // TODO do not allow GET requests, POST only
            if (ValidateGETChars("g")) {
                $gameID = requestInputQuery('g', null, 'integer');
            } else {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Bad request: parameters missing']);
                exit;
            }
        }
        $jrDevAllowed = checkIfSoleDeveloper($user, $gameID);
    } elseif ($field == 4) {
        if (ValidatePOSTChars("gl")) {
            $gameID = requestInputPost('g', null, 'integer');
            $achFlags = requestInputPost('l');
            if ($achFlags == AchievementType::Unofficial) {
                $jrDevAllowed = checkIfSoleDeveloper($user, $gameID);
            }
        } else {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Bad request: parameters missing']);
            exit;
        }
    }

    if (!$jrDevAllowed) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Forbidden']);
        exit;
    }
}

$commentText = null;
switch ($field) {
    case 1:
        // display order
        if (updateAchievementDisplayID((int) $achievementId, $value)) {
            echo json_encode(['success' => true, 'message' => 'OK']);
            exit;
        }
        break;
    case 2:
        // Embed video
        $value = str_replace("_http_", "http", $value);
        if (updateAchievementEmbedVideo((int) $achievementId, $value)) {
            echo json_encode(['success' => true, 'message' => 'OK']);
            exit;
        }
        break;
    case 3:
        // Flags
        if (!AchievementType::isValid((int) $value)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Bad request: invalid type flag (' . $value . ')'], JSON_THROW_ON_ERROR);
            exit;
        }

        $achievement = GetAchievementMetadataJSON((int) (is_array($achievementId) ? $achievementId[0] : $achievementId));
        if ((int) $value === AchievementType::OfficialCore && !isValidConsoleId($achievement['ConsoleID'])) {
            echo json_encode(['success' => false, 'error' => 'Invalid Console']);
            exit;
        }

        if (updateAchievementFlags($achievementId, (int) $value)) {
            if ($value == AchievementType::OfficialCore) {
                $commentText = 'promoted this achievement to the Core set';
            }
            if ($value == AchievementType::Unofficial) {
                $commentText = 'demoted this achievement to Unofficial';
            }
            addArticleComment("Server", ArticleType::Achievement, $achievementId, "\"$user\" $commentText.", $user);
            echo json_encode(['success' => true, 'message' => 'OK']);
            exit;
        }
        break;
    case 4:
        // Achievement Details
        $achievementTitle = requestInputPost('t');
        $desc = requestInputPost('d');
        $badgeName = requestInputPost('b');
        $achPoints = requestInputPost('p', null, 'integer');
        $achMem = requestInputPost('m');
        $achFlags = requestInputPost('l');
        $gameID = requestInputPost('g', null, 'integer');
        $errorOut = "";

        if (UploadNewAchievement(
                author: $user,
                gameID: $gameID,
                title: $achievementTitle,
                desc: $desc,
                progress: ' ',
                progressMax: ' ',
                progressFmt: ' ',
                points: $achPoints,
                mem: $achMem,
                type: $achFlags,
                idInOut: $achievementId,
                badge: $badgeName,
                errorOut: $errorOut
        )) {
            echo json_encode(['success' => true, 'message' => 'OK']);
            exit;
        } else {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Bad request']);
            break;
        }
}

echo json_encode(['success' => false, 'error' => 'Something went wrong']);
exit;

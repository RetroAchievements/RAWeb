<?php

use RA\ArticleType;
use RA\Permissions;

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../lib/bootstrap.php';

if (!authenticateFromCookie($user, $permissions, $userDetails, Permissions::JuniorDeveloper)) {
    redirect('?e=badcredentials');
    exit;
}

$achievementID = (int) requestInputPost('i', 0);
$gameID = (int) requestInputPost('g', 0);

if ($permissions == Permissions::JuniorDeveloper && !checkIfSoleDeveloper($user, $gameID)) {
    // Immediate redirect if the jr. dev attempting to upload the image is not the sole developer
    redirect("achievement/$achievementID?e=badcredentials");
    exit;
}

try {
    $imagePath = UploadBadgeImage($_FILES['file']);
} catch (Exception $exception) {
    echo json_encode([
        'Success' => false,
        'Error' => $exception->getMessage(),
    ]);
    redirect("achievement/$achievementID?e=uploadfailed");
    exit;
}

global $db;
$dbResult = mysqli_query($db, "UPDATE Achievements AS a SET BadgeName='$imagePath' WHERE a.ID = $achievementID");
if (!$dbResult) {
    redirect("achievement/$achievementID?e=uploadfailed");
    exit;
}

addArticleComment('Server', ArticleType::Achievement, $achievementID, "$user edited this achievement's badge.");

redirect("achievement/$achievementID?e=uploadok");

<?php

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../lib/bootstrap.php';

if (!ValidatePOSTChars("ipm")) {
    echo "FAILED";
    exit;
}

$achievementID = requestInputPost('i');
$problemType = requestInputPost('p');
$modeS = requestInputPost('m');
$hardcore = ($modeS === "2") ? 1 : 0;

$note = null;
if (isset($_POST['note'])) {
    $appendNote = $_POST['note']['description'];

    if (!empty(trim($_POST['note']['checksum']))) {
        $appendNote .= "\nRetroAchievements Hash: " . $_POST['note']['checksum'];
    }

    if (!empty(trim($_POST['note']['emulator']))) {
        $appendNote .= "\nEmulator: " . $_POST['note']['emulator'];

        if ($_POST['note']['emulator'] == "RetroArch" || $_POST['note']['emulator'] == "RALibRetro") {
            $appendNote .= " (" . $_POST['note']['core'] . ")";
        }
    }

    if (!empty(trim($_POST['note']['emulatorVersion']))) {
        $appendNote .= "\nEmulator Version: " . $_POST['note']['emulatorVersion'];
    }

    $note = $appendNote;
}

if (authenticateFromCookie($user, $permissions, $userDetail)) {
    $success = submitNewTickets($user, $achievementID, $problemType, $hardcore, $note, $msgOut);
    if ($msgOut == "FAILED!") {
        header("Location: " . getenv('APP_URL') . "/achievement/$achievementID?e=issue_failed");
    } else {
        header("Location: " . getenv('APP_URL') . "/achievement/$achievementID?e=issue_submitted");
    }

    echo $msgOut;
} else {
    echo "FAILED: Cannot validate user! Try logging out and back in, or confirming your email.";
}

<?php
require_once __DIR__ . '/../../lib/bootstrap.php';

$gameID = seekPOST('i');
settype($gameID, 'integer');

$developer = seekPOST('d');
$publisher = seekPOST('p');
$genre = seekPOST('g');
$released = seekPOST('r');

$richPresence = seekPOST('x');

$newGameAlt = seekPOST('n');
$removeGameAlt = seekPOST('m');

$newForumTopic = seekPOST('f');
settype($newForumTopic, 'integer');

if (RA_ReadCookieCredentials($user, $points, $truePoints, $unreadMessageCount, $permissions, \RA\Permissions::SuperUser)) {
    if (isset($richPresence)) {
        requestModifyRichPresence($gameID, $richPresence);
        header("location: " . getenv('APP_URL') . "/Game/$gameID?e=ok");
        exit;
    } else {
        if (isset($newGameAlt) || isset($removeGameAlt)) {
            //	new alt provided/alt to be removed
            error_log("Provided $newGameAlt and $removeGameAlt to submitgamedata");
            requestModifyGameAlt($gameID, $newGameAlt, $removeGameAlt);
            header("location: " . getenv('APP_URL') . "/Game/$gameID?e=ok");
            exit;
        } else {
            if (isset($developer) && isset($publisher) && isset($genre) && isset($released)) {
                requestModifyGameData($gameID, $developer, $publisher, $genre, $released);
                header("location: " . getenv('APP_URL') . "/Game/$gameID?e=ok");
                exit;
            } else {
                if (isset($newForumTopic)) {
                    if (requestModifyGameForumTopic($gameID, $newForumTopic)) {
                        header("location: " . getenv('APP_URL') . "/Game/$gameID?e=ok");
                        exit;
                    } else {
                        header("location: " . getenv('APP_URL') . "/Game/$gameID?e=error");
                        exit;
                    }
                } else {
                    //	unknown?
                    header("location: " . getenv('APP_URL') . "/Game/$gameID?e=unrecognised");
                    exit;
                }
            }
        }
    }
} else {
    header("location: " . getenv('APP_URL') . "/Game/$gameID?e=notloggedin");
    exit;
}

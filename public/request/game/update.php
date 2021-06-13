<?php

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../lib/bootstrap.php';

$gameID = requestInputPost('i', null, 'integer');

$developer = requestInputPost('d');
$publisher = requestInputPost('p');
$genre = requestInputPost('g');
$released = requestInputPost('r');

$richPresence = requestInputPost('x');

$newGameAlt = requestInputPost('n');
$removeGameAlt = requestInputPost('m');

$newForumTopic = requestInputPost('f', null, 'integer');

if (RA_ReadCookieCredentials($user, $points, $truePoints, $unreadMessageCount, $permissions, \RA\Permissions::SuperUser)) {
    if (isset($richPresence)) {
        requestModifyRichPresence($gameID, $richPresence);
        header("location: " . getenv('APP_URL') . "/game/$gameID?e=ok");
        exit;
    } else {
        if (isset($newGameAlt) || isset($removeGameAlt)) {
            //	new alt provided/alt to be removed
            // error_log("Provided $newGameAlt and $removeGameAlt to submitgamedata");
            requestModifyGameAlt($gameID, $newGameAlt, $removeGameAlt);
            header("location: " . getenv('APP_URL') . "/game/$gameID?e=ok");
            exit;
        } else {
            if (isset($developer) && isset($publisher) && isset($genre) && isset($released)) {
                requestModifyGameData($gameID, $developer, $publisher, $genre, $released);
                header("location: " . getenv('APP_URL') . "/game/$gameID?e=ok");
                exit;
            } else {
                if (isset($newForumTopic)) {
                    if (requestModifyGameForumTopic($gameID, $newForumTopic)) {
                        header("location: " . getenv('APP_URL') . "/game/$gameID?e=ok");
                        exit;
                    } else {
                        header("location: " . getenv('APP_URL') . "/game/$gameID?e=error");
                        exit;
                    }
                } else {
                    //	unknown?
                    header("location: " . getenv('APP_URL') . "/game/$gameID?e=unrecognised");
                    exit;
                }
            }
        }
    }
} else {
    header("location: " . getenv('APP_URL') . "/game/$gameID?e=notloggedin");
    exit;
}

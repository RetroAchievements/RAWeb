<?php

use App\Support\Shortcode\Shortcode;
use RA\ArticleType;
use RA\Permissions;
use RA\SubscriptionSubjectType;
use RA\UserAction;

authenticateFromCookie($user, $permissions, $userDetails);

$articleTypeID = requestInputSanitized('t', 0, 'integer');
$articleID = requestInputSanitized('i', 0, 'integer');

if ($articleID == 0 || !ArticleType::isValid($articleTypeID)) {
    abort(404);
}

$offset = requestInputSanitized('o', 0, 'integer');
$count = 25;

$commentData = [];
$numArticleComments = getArticleComments($articleTypeID, $articleID, $offset, $count, $commentData);

switch ($articleTypeID)
{
    case ArticleType::Game:
        $gameData = getGameData($articleID);
        if ($gameData === null) {
            abort(404);
        }
        $pageTitle = $gameData['Title'] . ' (' . $gameData['ConsoleName'] . ')';
        $navPath =
        [
            'All Games' => '/gameList.php',
            $gameData['ConsoleName'] => '/gameList.php?c=' . $gameData['ConsoleID'],
            $gameData['Title'] => '/game/' . $gameData['ID']
        ];
        break;

    case ArticleType::Achievement:
        $pageTitle = getAchievementTitle($articleID, $gameTitle, $gameID);
        if (empty($pageTitle)) {
            abort(404);
        }
        $gameData = getGameData($gameID);
        if ($gameData === null) {
            abort(404);
        }
        $navPath =
        [
            'All Games' => '/gameList.php',
            $gameData['ConsoleName'] => '/gameList.php?c=' . $gameData['ConsoleID'],
            $gameData['Title'] => '/game/' . $gameData['ID'],
            $pageTitle => '/achievement/' . $articleID
        ];
        break;

    case ArticleType::User:
        $pageTitle = getUserFromID($articleID);
        if (empty($pageTitle) || !getAccountDetails($pageTitle, $userData)) {
            abort(404);
        }
        $navPath =
        [
            'All Users' => '/userList.php',
            $pageTitle => '/user/' . $pageTitle
        ];
        break;

    default:
        abort(404);
        break;
}

//if ($permissions < $topicData['RequiredPermissions']) {
//    abort(403);
//}

RenderContentStart("Comments: $pageTitle");
?>
<div id="mainpage">
    <div id="fullcontainer">
        <?php
            echo "<div class='navpath'>";
            foreach ($navPath as $text => $link) {
                echo "<a href='$link'>$text</a> &raquo; ";
            }
            echo "<b>Comments</b></div>";

            echo "<h2>$pageTitle</h2>";

            RenderCommentsComponent($user, $numArticleComments, $commentData, $articleID, $articleTypeID, $permissions, $count, $offset, embedded: false);
        ?>
        <br>
        <div class='flex justify-between mb-3'><div>
        <?php
            if ($numArticleComments > count($commentData)) {
                RenderPaginator($numArticleComments, $count, $offset, "/comments.php?t=$articleTypeID&i=$articleID&o=");
            }
        ?>
        </div></div>
    </div>
</div>
<?php RenderContentEnd(); ?>

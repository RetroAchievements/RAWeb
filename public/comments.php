<?php

use LegacyApp\Community\Enums\ArticleType;
use LegacyApp\Site\Enums\Permissions;

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

$commentsLabel = "Comments";
switch ($articleTypeID)
{
    case ArticleType::Game:
        $gameData = getGameData($articleID);
        if ($gameData === null) {
            abort(404);
        }
        $pageTitle = renderGameTitle($gameData['Title'] . ' (' . $gameData['ConsoleName'] . ')');
        $navPath =
        [
            '_GamePrefix' => renderGameBreadcrumb($gameData),
        ];
        break;

    case ArticleType::GameHash:
        if ($permissions < Permissions::Developer) {
            abort(403);
        }
        $gameData = getGameData($articleID);
        if ($gameData === null) {
            abort(404);
        }
        $pageTitle = renderGameTitle($gameData['Title'] . ' (' . $gameData['ConsoleName'] . ')');
        $commentsLabel = "Hash Comments";
        $navPath =
        [
            '_GamePrefix' => renderGameBreadcrumb($gameData),
            'Manage Hashes' => '/managehashes.php?g=' . $gameData['ID'],
        ];
        break;

    case ArticleType::GameModification:
        if ($permissions < Permissions::JuniorDeveloper) {
            abort(403);
        }
        $gameData = getGameData($articleID);
        if ($gameData === null) {
            abort(404);
        }
        $pageTitle = renderGameTitle($gameData['Title'] . ' (' . $gameData['ConsoleName'] . ')');
        $commentsLabel = "Modifications";
        $navPath =
        [
            '_GamePrefix' => renderGameBreadcrumb($gameData),
        ];
        break;

    case ArticleType::SetClaim:
        if ($permissions < Permissions::Admin) {
            abort(403);
        }
        $gameData = getGameData($articleID);
        if ($gameData === null) {
            abort(404);
        }
        $pageTitle = renderGameTitle($gameData['Title'] . ' (' . $gameData['ConsoleName'] . ')');
        $commentsLabel = "Claim Comments";
        $navPath =
        [
            '_GamePrefix' => renderGameBreadcrumb($gameData),
            'Manage Claims' => '/manageclaims.php?g=' . $gameData['ID'],
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
            '_GamePrefix' => renderGameBreadcrumb($gameData),
            $pageTitle => '/achievement/' . $articleID,
        ];
        break;

    case ArticleType::Leaderboard:
        $pageTitle = getleaderboardTitle($articleID, $gameID);
        if (empty($pageTitle)) {
            abort(404);
        }
        $gameData = getGameData($gameID);
        if ($gameData === null) {
            abort(404);
        }
        $navPath =
        [
            '_GamePrefix' => renderGameBreadcrumb($gameData),
            $pageTitle => '/leaderboard/' . $articleID,
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
            $pageTitle => '/user/' . $pageTitle,
        ];
        break;

    case ArticleType::UserModeration:
        if ($permissions < Permissions::Admin) {
            abort(403);
        }
        $pageTitle = getUserFromID($articleID);
        if (empty($pageTitle) || !getAccountDetails($pageTitle, $userData)) {
            abort(404);
        }
        $commentsLabel = "Moderation Comments";
        $navPath =
        [
            'All Users' => '/userList.php',
            $pageTitle => '/user/' . $pageTitle,
        ];
        break;

    case ArticleType::AchievementTicket:
        $ticket = getTicket($articleID);
        if ($ticket == null) {
            abort(404);
        }
        $pageTitle = "Ticket $articleID: " . $ticket['AchievementTitle'];
        $navPath =
        [
            'Ticket Manager' => '/ticketmanager.php',
            $articleID => '/ticketmanager.php?i=' . $articleID,
        ];
        break;

    default:
        abort(404);
}

RenderContentStart("$commentsLabel: $pageTitle");
?>
<div id="mainpage">
    <div id="fullcontainer">
        <?php
            echo "<div class='navpath'>";
            if (array_key_first($navPath) === '_GamePrefix') {
                // Render game breadcrumb prefix
                echo $navPath['_GamePrefix'] . " &raquo; ";
                array_shift($navPath);
            }
            foreach ($navPath as $text => $link) {
                echo "<a href='$link'>$text</a> &raquo; ";
            }
            echo "<b>$commentsLabel</b></div>";

            echo "<h3>$pageTitle</h3>";

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

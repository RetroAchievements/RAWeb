<?php

use LegacyApp\Community\Enums\ArticleType;
use LegacyApp\Community\Models\Ticket;
use LegacyApp\Platform\Models\Achievement;
use LegacyApp\Platform\Models\Game;
use LegacyApp\Platform\Models\Leaderboard;
use LegacyApp\Platform\Models\System;
use LegacyApp\Site\Enums\Permissions;
use LegacyApp\Site\Models\User;

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
        /** @var Game $game */
        $game = Game::findOrFail($articleID);
        /** @var System $console */
        $console = $game->console;
        $pageTitle = renderGameTitle($game->Title . ' (' . $console->Name . ')');
        $navPath = [
            '_GamePrefix' => renderGameBreadcrumb($game->ID),
        ];
        break;

    case ArticleType::GameHash:
        if ($permissions < Permissions::Developer) {
            abort(403);
        }
        /** @var Game $game */
        $game = Game::findOrFail($articleID);
        /** @var System $console */
        $console = $game->console;
        $pageTitle = renderGameTitle($game->Title . ' (' . $console->Name . ')');
        $commentsLabel = "Hash Comments";
        $navPath = [
            '_GamePrefix' => renderGameBreadcrumb($game->ID),
            'Manage Hashes' => '/managehashes.php?g=' . $game->ID,
        ];
        break;

    case ArticleType::GameModification:
        if ($permissions < Permissions::JuniorDeveloper) {
            abort(403);
        }
        /** @var Game $game */
        $game = Game::findOrFail($articleID);
        /** @var System $console */
        $console = $game->console;
        $pageTitle = renderGameTitle($game->Title . ' (' . $console->Name . ')');
        $commentsLabel = "Modifications";
        $navPath =
        [
            '_GamePrefix' => renderGameBreadcrumb($game->ID),
        ];
        break;

    case ArticleType::SetClaim:
        if ($permissions < Permissions::Admin) {
            abort(403);
        }
        /** @var Game $game */
        $game = Game::findOrFail($articleID);
        /** @var System $console */
        $console = $game->console;
        $pageTitle = renderGameTitle($game->Title . ' (' . $console->Name . ')');
        $commentsLabel = "Claim Comments";
        $navPath = [
            '_GamePrefix' => renderGameBreadcrumb($game->ID),
            'Manage Claims' => '/manageclaims.php?g=' . $game->ID,
        ];
        break;

    case ArticleType::Achievement:
        /** @var Achievement $achievement */
        $achievement = Achievement::findOrFail($articleID);
        /** @var Game $game */
        $game = Game::findOrFail($achievement->GameID);
        $pageTitle = $achievement->Title;
        $navPath = [
            '_GamePrefix' => renderGameBreadcrumb($game->ID),
            $pageTitle => '/achievement/' . $achievement->ID,
        ];
        break;

    case ArticleType::Leaderboard:
        /** @var Leaderboard $leaderboard */
        $leaderboard = Leaderboard::findOrFail($articleID);
        /** @var Game $game */
        $game = Game::findOrFail($leaderboard->GameID);
        $pageTitle = $leaderboard->Title;
        $navPath = [
            '_GamePrefix' => renderGameBreadcrumb($game->ID),
            $pageTitle => '/leaderboard/' . $leaderboard->ID,
        ];
        break;

    case ArticleType::User:
        /** @var User $user */
        $user = User::findOrFail($articleID);
        $pageTitle = $user->User;
        $navPath = [
            'All Users' => '/userList.php',
            $user->User => '/user/' . $user->User,
        ];
        break;

    case ArticleType::UserModeration:
        if ($permissions < Permissions::Admin) {
            abort(403);
        }
        /** @var User $user */
        $user = User::findOrFail($articleID);
        $pageTitle = $user->User;
        $commentsLabel = "Moderation Comments";
        $navPath = [
            'All Users' => '/userList.php',
            $user->User => '/user/' . $user->User,
        ];
        break;

    case ArticleType::AchievementTicket:
        /** @var Ticket $ticket */
        $ticket = Ticket::findOrFail($articleID);
        $pageTitle = "Ticket $articleID: " . $ticket->achievement->Title;
        $navPath = [
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

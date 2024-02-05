<?php

use App\Community\Enums\ArticleType;
use App\Models\Ticket;
use App\Enums\Permissions;
use App\Models\User;
use App\Models\Achievement;
use App\Models\Game;
use App\Models\Leaderboard;
use App\Models\System;

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

/** @var ?Game $game */
$game = null;

$commentsLabel = "Comments";
switch ($articleTypeID) {
    case ArticleType::Game:
        $game = Game::findOrFail($articleID);
        /** @var System $console */
        $console = $game->console;
        $pageTitle = $game->Title . ' (' . $console->Name . ')';
        $navPath = [
            '_GamePrefix' => renderGameBreadcrumb($game->ID),
        ];
        break;

    case ArticleType::GameHash:
        if ($permissions < Permissions::Developer) {
            abort(403);
        }
        $game = Game::findOrFail($articleID);
        /** @var System $console */
        $console = $game->console;
        $pageTitle = $game->Title . ' (' . $console->Name . ')';
        $commentsLabel = "Hash Comments";
        $navPath = [
            '_GamePrefix' => renderGameBreadcrumb($game->ID),
            'Manage Hashes' => route('game.hash.manage', $game->ID),
        ];
        break;

    case ArticleType::GameModification:
        if ($permissions < Permissions::JuniorDeveloper) {
            abort(403);
        }
        $game = Game::findOrFail($articleID);
        /** @var System $console */
        $console = $game->console;
        $pageTitle = $game->Title . ' (' . $console->Name . ')';
        $commentsLabel = "Modifications";
        $navPath =
        [
            '_GamePrefix' => renderGameBreadcrumb($game->ID),
        ];
        break;

    case ArticleType::SetClaim:
        if ($permissions < Permissions::Moderator) {
            abort(403);
        }
        $game = Game::findOrFail($articleID);
        /** @var System $console */
        $console = $game->console;
        $pageTitle = $game->Title . ' (' . $console->Name . ')';
        $commentsLabel = "Claim Comments";
        $navPath = [
            '_GamePrefix' => renderGameBreadcrumb($game->ID),
            'Manage Claims' => '/manageclaims.php?g=' . $game->ID,
        ];
        break;

    case ArticleType::Achievement:
        /** @var Achievement $achievement */
        $achievement = Achievement::findOrFail($articleID);
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
        /** @var User $pageUser */
        $pageUser = User::findOrFail($articleID);

        if (!$pageUser->UserWallActive) {
            abort(401);
        }

        $pageTitle = $pageUser->User;
        $navPath = [
            'All Users' => '/userList.php',
            $pageUser->User => '/user/' . $pageUser->User,
        ];
        break;

    case ArticleType::UserModeration:
        if ($permissions < Permissions::Moderator) {
            abort(403);
        }
        /** @var User $pageUser */
        $pageUser = User::findOrFail($articleID);
        $pageTitle = $pageUser->User;
        $commentsLabel = "Moderation Comments";
        $navPath = [
            'All Users' => '/userList.php',
            $pageUser->User => '/user/' . $pageUser->User,
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
?>
<x-app-layout pageTitle="{{ $commentsLabel }}: {{ $pageTitle }}">
    <div class="navpath">
        <?php
        if (array_key_first($navPath) === '_GamePrefix') {
            // Render game breadcrumb prefix
            echo $navPath['_GamePrefix'] . " &raquo; ";
            array_shift($navPath);
        }
        ?>
        @foreach ($navPath as $text => $link)
            <a href="{{ $link }}">{{ $text }}</a> &raquo;
        @endforeach
        <b>{{ $commentsLabel }}</b>
    </div>
    @if ($game
        && (
            $articleTypeID == ArticleType::Game
            || $articleTypeID == ArticleType::GameHash
            || $articleTypeID == ArticleType::GameModification
            || $articleTypeID == ArticleType::SetClaim
        )
    )
        <x-game.heading
            :gameId="$game->ID"
            :gameTitle="$game->Title"
            :consoleId="$game->Console->ID"
            :consoleName="$game->Console->Name"
        />
    @else
        <h3>{{ $pageTitle }}</h3>
    @endif
    <?php
    RenderCommentsComponent($user, $numArticleComments, $commentData, $articleID, $articleTypeID, $permissions, $count, $offset, embedded: false);
    ?>
    <br>
    <div class='flex justify-between mb-3'>
        <div>
            <?php
                if ($numArticleComments > count($commentData)) {
                    RenderPaginator($numArticleComments, $count, $offset, "/comments.php?t=$articleTypeID&i=$articleID&o=");
                }
            ?>
        </div>
    </div>
</x-app-layout>

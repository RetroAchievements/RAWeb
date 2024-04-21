<?php

use App\Community\Enums\ArticleType;
use App\Enums\Permissions;
use App\Models\Game;
use App\Models\Leaderboard;
use App\Platform\Enums\ValueFormat;

authenticateFromCookie($user, $permissions, $userDetails);

$lbID = requestInputSanitized('i', null, 'integer');
if (empty($lbID)) {
    $lbID = (int) request('leaderboard');
    if (empty($lbID)) {
        abort(404);
    }
}

$offset = requestInputSanitized('o', 0, 'integer');
$count = requestInputSanitized('c', 50, 'integer');
$friendsOnly = requestInputSanitized('f', 0, 'integer');

$lbData = GetLeaderboardData($lbID, $user, $count, $offset);
$leaderboard = Leaderboard::with('entries.user')->find($lbID);

if (!$leaderboard) {
    abort(404);
}

$numEntries = $leaderboard->entries->count();
$orderedEntries = $leaderboard->entries()
    ->whereHas('user') // Don't include entries from deleted users.
    ->with('user')
    ->offset($offset)
    ->limit($count)
    ->orderBy('score', $leaderboard->rank_asc ? 'ASC' : 'DESC')
    ->get();

$lbTitle = $leaderboard->title;
$lbDescription = $leaderboard->description;
$lbFormat = $leaderboard->format;
$lbAuthor = $leaderboard?->authorUser?->User;
$lbCreated = $leaderboard->created_at;
$lbUpdated = $leaderboard->updated_at;
$lbMemory = $leaderboard->Mem;
$sortDesc = !$leaderboard->rank_asc;

$gameID = $leaderboard->game->id;
$gameTitle = $leaderboard->game->title;
$gameIcon = $leaderboard->game->ImageIcon;
$consoleID = $leaderboard->game->system->id;
$consoleName = $leaderboard->game->system->name;
$forumTopicID = $leaderboard->game->ForumTopicID;

$pageTitle = "$lbTitle in $gameTitle ($consoleName)";

$numArticleComments = getRecentArticleComments(ArticleType::Leaderboard, $lbID, $commentData);
?>
<x-app-layout
    :pageTitle="$pageTitle"
    pageDescription="{{ $lbDescription ?? $lbTitle }}, {{ $numEntries }} entries."
    :pageImage="media_asset($gameIcon)"
    pageType="retroachievements:leaderboard"
>
    <div id="lbinfo">
        <x-game.breadcrumbs 
            :game="$leaderboard->game"
            :currentPageLabel="$lbTitle"
        />

        <x-game.heading
            :consoleId="$consoleID"
            :consoleName="$consoleName"
            :gameTitle="$gameTitle"
        />
        <?php
        echo "<table class='nicebox'><tbody>";

        echo "<tr>";
        echo "<td style='width:70px' class='p-0'>";
        echo gameAvatar($lbData, label: false, iconSize: 96);
        echo "</td>";

        echo "<td class='px-3'>";
        echo "<div class='flex justify-between'>";
        echo "<div>";
        echo "<a href='/leaderboard/$lbID'><strong>$lbTitle</strong></a><br>";
        echo "$lbDescription";
        echo "</div>";
        echo "</div>";
        echo "</td>";

        echo "</tr>";
        echo "</tbody></table>";

        $niceDateCreated = date("d M, Y H:i", strtotime($lbCreated));
        $niceDateModified = date("d M, Y H:i", strtotime($lbUpdated));

        echo "<p class='embedded smalldata my-2'>";
        echo "<small>";
        if (is_null($lbAuthor)) {
            echo "Created on: $niceDateCreated<br>Last modified: $niceDateModified<br>";
        } else {
            echo "Created by " . userAvatar($lbAuthor, icon: false) . " on: $niceDateCreated<br>Last modified: $niceDateModified<br>";
        }
        echo "</small>";
        echo "</p>";

        if (isset($user) && $permissions >= Permissions::JuniorDeveloper) {
            echo "<div class='devbox'>";
            echo "<span onclick=\"$('#devboxcontent').toggle(); return false;\">Dev ▼</span>";
            echo "<div id='devboxcontent' style='display: none'>";

            echo "<ul>";
            echo "<a href='/leaderboardList.php?g=$gameID'>Leaderboard Management for $gameTitle</a>";

            echo "<li>Manage Entries</li>";
            echo "<div>";
            if (!$orderedEntries->isEmpty()) {
                echo "<tr><td>";
                echo "<form method='post' action='/request/leaderboard/remove-entry.php' onsubmit='return confirm(\"Are you sure you want to permanently delete this leaderboard entry?\")'>";
                echo csrf_field();
                echo "<input type='hidden' name='leaderboard' value='$lbID' />";
                echo "Remove Entry:";
                echo "<select name='user'>";
                echo "<option selected>-</option>";
                foreach ($orderedEntries as $nextLBEntry) {
                    // Display all entries for devs, display only own entry for jr. devs
                    if (
                        ($user === $nextLBEntry->user->User && $permissions === Permissions::JuniorDeveloper)
                        || $permissions >= Permissions::Developer
                    ) {
                        $nextUser = $nextLBEntry->user->User;
                        $nextScore = $nextLBEntry->score;
                        $nextScoreFormatted = ValueFormat::format($nextScore, $lbFormat);
                        echo "<option value='$nextUser'>$nextUser ($nextScoreFormatted)</option>";
                    }
                }
                echo "</select>";
                echo "</br>";
                echo "Reason:";
                echo "<input type='text' name='reason' maxlength='200' style='width: 50%;' placeholder='Please enter reason for removal'>";
                echo "<button class='btn btn-danger'>Remove entry</button>";
                echo "</form>";
                echo "</td></tr>";
            }
            echo "</div>";

            echo "<br/>";

            $memStart = "";
            $memCancel = "";
            $memSubmit = "";
            $memValue = "";
            $memChunks = explode("::", $lbMemory);
            foreach ($memChunks as &$memChunk) {
                $part = substr($memChunk, 0, 4);
                if ($part == 'STA:') {
                    $memStart = substr($memChunk, 4);
                } elseif ($part == 'CAN:') {
                    $memCancel = substr($memChunk, 4);
                } elseif ($part == 'SUB:') {
                    $memSubmit = substr($memChunk, 4);
                } elseif ($part == 'VAL:') {
                    $memValue = substr($memChunk, 4);
                }
            }

            getCodeNotes($gameID, $codeNotes);
            ExplainLeaderboardTrigger('Start', $memStart, $codeNotes);
            ExplainLeaderboardTrigger('Cancel', $memCancel, $codeNotes);
            ExplainLeaderboardTrigger('Submit', $memSubmit, $codeNotes);
            ExplainLeaderboardTrigger('Value', $memValue, $codeNotes);

            echo "</div>";
            echo "</div>";
        }

        echo "<table class='table-highlight'><tbody>";
        echo "<tr class='do-not-highlight'><th>Rank</th><th>User</th><th class='text-right'>Result</th><th class='text-right'>Date Submitted</th></tr>";

        $numActualEntries = 0;
        $localUserFound = false;
        $resultsDrawn = 0;
        $previousScore = null;
        $currentRank = 1;
        foreach ($orderedEntries as $nextEntry) {
            $nextUser = $nextEntry->user->User;
            $nextScore = $nextEntry->score;
            $nextScoreFormatted = ValueFormat::format($nextScore, $lbFormat);
            $nextSubmitAt = $nextEntry->created_at;
            $nextSubmitAtNice = getNiceDate(strtotime($nextSubmitAt));

            if ($previousScore !== null && $previousScore !== $nextScore) {
                $currentRank = $resultsDrawn + 1;
            }
            $previousScore = $nextScore;

            $isLocal = (strcmp($nextUser, $user) == 0);
            $lastEntry = ($resultsDrawn + 1 == $numEntries);
            $userAppendedInResults = ($numEntries > $count);

            if ($lastEntry && $isLocal && $userAppendedInResults) {
                // This is the local, outside-rank user at the end of the table
                echo "<tr class='last'><td colspan='4' class='small'>&nbsp;</td></tr>"; // Dirty!
            } else {
                $numActualEntries++;
            }

            if ($isLocal) {
                $localUserFound = true;
                echo "<tr style='outline: thin solid'>";
            } else {
                echo "<tr>";
            }

            $injectFmt1 = $isLocal ? "<b>" : "";
            $injectFmt2 = $isLocal ? "</b>" : "";

            echo "<td class='lb_rank'>$injectFmt1$currentRank$injectFmt2</td>";

            echo "<td class='lb_user'>";
            echo userAvatar($nextUser);
            echo "</td>";

            echo "<td class='lb_result text-right'>$injectFmt1$nextScoreFormatted$injectFmt2</td>";

            echo "<td class='lb_date text-right smalldate'>$injectFmt1$nextSubmitAtNice$injectFmt2</td>";

            echo "</tr>";

            $resultsDrawn++;
        }

        echo "</tbody></table><br>";

        if (!$localUserFound && isset($user)) {
            echo "<div>You don't appear to be ranked for this leaderboard. Why not give it a go?</div><br>";
        }

        echo "<div class='text-right'>";
        if ($offset > 0) {
            $prevOffset = $offset - $count;
            echo "<a class='btn btn-link' href='/leaderboardinfo.php?i=$lbID&amp;o=$prevOffset&amp;c=$count&amp;f=$friendsOnly'>&lt; Previous $count</a> - ";
        }

        if ($numActualEntries == $count) {
            // Max number fetched, i.e. there are more. Can goto next 20.
            $nextOffset = $offset + $count;
            echo "<a class='btn btn-link' href='/leaderboardinfo.php?i=$lbID&amp;o=$nextOffset&amp;c=$count&amp;f=$friendsOnly'>Next $count &gt;</a>";
        }
        echo "</div>";

        // Render article comments
        RenderCommentsComponent(
            $user,
            $numArticleComments,
            $commentData,
            $lbID,
            ArticleType::Leaderboard,
            $permissions
        );

        RenderLinkToGameForum($gameTitle, $gameID, $forumTopicID, $permissions);
        echo "<br><br>";
        ?>
    </div>
    
    <x-slot name="sidebar">
        <x-game.leaderboards-listing :game="$leaderboard->game" />
    </x-slot>
</x-app-layout>

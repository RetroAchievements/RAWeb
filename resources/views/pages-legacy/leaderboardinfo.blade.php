<?php

use App\Community\Enums\ArticleType;
use App\Enums\Permissions;
use App\Models\Leaderboard;
use App\Models\User;
use App\Platform\Actions\GetRankedLeaderboardEntriesAction;
use App\Platform\Enums\ValueFormat;
use App\Platform\Services\TriggerDecoderService;
use Illuminate\Support\Facades\Blade;

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

$leaderboard = Leaderboard::find($lbID);
if (!$leaderboard) {
    abort(404);
}

$userModel = Auth::user();
$lbEntries = (new GetRankedLeaderboardEntriesAction())->execute($leaderboard, $offset, $count);

$numEntries = count($lbEntries);
$totalEntries = $leaderboard->entries()->count();
$lbTitle = $leaderboard->title;
$lbDescription = $leaderboard->description;
$lbFormat = $leaderboard->format;
$lbAuthor = $leaderboard?->developer?->display_name;
$lbCreated = $leaderboard->created_at;
$lbUpdated = $leaderboard->updated_at;
$lbMemory = $leaderboard->Mem;

$gameID = $leaderboard->game->id;
$gameTitle = $leaderboard->game->title;
$gameIcon = $leaderboard->game->ImageIcon;
$consoleID = $leaderboard->game->system->id;
$consoleName = $leaderboard->game->system->name;
$forumTopicID = $leaderboard->game->ForumTopicID;

$pageTitle = "$lbTitle in $gameTitle ($consoleName)";

?>

<x-app-layout
    :pageTitle="$pageTitle"
    pageDescription="{{ $lbDescription ?? $lbTitle }}, {{ $totalEntries }} entries."
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
        echo gameAvatar($leaderboard->game->toArray(), label: false, iconSize: 96);
        echo "</td>";

        echo "<td class='px-3'>";
        echo "<div class='flex justify-between'>";
        echo "<div>";
        echo "<a href='/leaderboard/$lbID'><strong>$lbTitle</strong></a><br>";
        echo "$lbDescription";
        echo "<br><span class='smalltext'>$totalEntries entries</span>";
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

        if ($userModel && $userModel->can('update', $leaderboard)) {
            echo '<a class="btn mb-1" href="' . route('filament.admin.resources.leaderboards.edit', ['record' => $leaderboard->id]) . '">Manage</a>';
        } elseif ($userModel && $userModel->can('manage', $leaderboard)) {
            echo '<a class="btn mb-1" href="' . route('filament.admin.resources.leaderboards.view', ['record' => $leaderboard->id]) . '">Manage</a>';
        }

        if (isset($user) && $permissions >= Permissions::JuniorDeveloper) {
            echo "<div>";
            echo "<button class='btn' id='devboxbutton' onclick=\"toggleExpander('devboxbutton', 'devboxcontent');\">Dev ▼</button>";
            echo "<div id='devboxcontent' class='hidden devboxcontainer'>";

            echo "<ul>";
            $manageLeaderboardsRoute = route('filament.admin.resources.leaderboards.index', [
                'tableFilters[game][id]' => $gameID,
                'tableSortColumn' => 'DisplayOrder',
                'tableSortDirection' => 'asc',
            ]);
            echo "<a href='$manageLeaderboardsRoute'>Leaderboard Management for $gameTitle</a>";

            echo "<li>Manage Entries</li>";
            echo "<div>";
            if (!empty($lbEntries)) {
                echo "<tr><td>";
                echo "<form method='post' action='/request/leaderboard/remove-entry.php' onsubmit='return confirm(\"Are you sure you want to permanently delete this leaderboard entry?\")'>";
                echo csrf_field();
                echo "<input type='hidden' name='leaderboard' value='$lbID' />";
                echo "Remove Entry:";
                echo "<select name='user'>";
                echo "<option selected>-</option>";
                foreach ($lbEntries as $nextLBEntry) {
                    // Display all entries for devs, display only own entry for jr. devs
                    // TODO use a policy
                    if ($permissions === Permissions::JuniorDeveloper && !$nextLBEntry->user->is($userModel)) {
                        continue;
                    }

                    $nextUser = $nextLBEntry->user->display_name;
                    $nextScore = $nextLBEntry->score;
                    $nextScoreFormatted = ValueFormat::format($nextScore, $lbFormat);
                    echo "<option value='$nextUser'>$nextUser ($nextScoreFormatted)</option>";
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

            $triggerDecoderService = new TriggerDecoderService();

            $groups = $triggerDecoderService->decode($memStart);
            $triggerDecoderService->addCodeNotes($groups, $gameID);
            echo Blade::render("<x-leaderboard.trigger-part :groups=\"\$groups\" :definition=\"\$definition\" :header=\"\$header\" />",
                ['groups' => $groups, 'definition' => $memStart, 'header' => 'Start']
            );

            $groups = $triggerDecoderService->decode($memCancel);
            $triggerDecoderService->addCodeNotes($groups, $gameID);
            echo Blade::render("<x-leaderboard.trigger-part :groups=\"\$groups\" :definition=\"\$definition\" :header=\"\$header\" />",
                ['groups' => $groups, 'definition' => $memCancel, 'header' => 'Cancel']
            );

            $groups = $triggerDecoderService->decode($memSubmit);
            $triggerDecoderService->addCodeNotes($groups, $gameID);
            echo Blade::render("<x-leaderboard.trigger-part :groups=\"\$groups\" :definition=\"\$definition\" :header=\"\$header\" />",
                ['groups' => $groups, 'definition' => $memSubmit, 'header' => 'Submit']
            );

            $groups = $triggerDecoderService->decodeValue($memValue);
            $triggerDecoderService->addCodeNotes($groups, $gameID);
            echo Blade::render("<x-leaderboard.trigger-part :groups=\"\$groups\" :definition=\"\$definition\" :header=\"\$header\" />",
                ['groups' => $groups, 'definition' => $memValue, 'header' => 'Value']
            );

            echo "</div>";
            echo "</div>";
        }

        echo "<table class='table-highlight'><tbody>";
        echo "<tr class='do-not-highlight'><th>Rank</th><th>User</th><th class='text-right'>Result</th><th class='text-right'>Date Submitted</th></tr>";

        $localUserFound = false;
        $resultsDrawn = 0;
        $nextRank = 1;

        foreach ($lbEntries as $nextEntry) {
            $nextUser = $nextEntry->user->display_name;
            $nextScore = $nextEntry->score;
            $nextRank = $nextEntry->rank;
            $nextScoreFormatted = ValueFormat::format($nextScore, $lbFormat);
            $nextSubmitAt = $nextEntry->updated_at->unix();
            $nextSubmitAtNice = getNiceDate($nextSubmitAt);

            $isLocal = $nextEntry->user->is($userModel);
            if ($isLocal) {
                $localUserFound = true;
                echo "<tr style='outline: thin solid'>";
            } else {
                echo "<tr>";
            }

            $injectFmt1 = $isLocal ? "<b>" : "";
            $injectFmt2 = $isLocal ? "</b>" : "";

            echo "<td class='lb_rank'>$injectFmt1$nextRank$injectFmt2</td>";

            echo "<td class='lb_user'>";
            echo userAvatar($nextUser);
            echo "</td>";

            echo "<td class='lb_result text-right'>$injectFmt1$nextScoreFormatted$injectFmt2</td>";

            echo "<td class='lb_date text-right smalldate'>$injectFmt1$nextSubmitAtNice$injectFmt2</td>";

            echo "</tr>";

            $resultsDrawn++;
        }

        if (!$localUserFound && isset($user)) {
            $userEntry = $leaderboard->entries(includeUnrankedUsers: true)
                ->where('user_id', '=', $userModel->id)
                ->first();
            if ($userEntry) {
                $userRank = $leaderboard->getRank($userEntry->score);
                if (!$userModel->isRanked()) {
                    $userRank = "($userRank)";
                }

                $userScoreFormatted = ValueFormat::format($userEntry->score, $lbFormat);
                $userSubmitAtNice = getNiceDate($userEntry->updated_at->unix());

                // This is the local, outside-rank user at the end of the table
                echo "<tr class='last'><td colspan='4' class='small'>&nbsp;</td></tr>"; // Dirty!
                
                echo "<tr style='outline: thin solid'>";
                echo "<td class='lb_rank'><b>$userRank</b></td>";
                echo "<td class='lb_user'>";
                echo userAvatar($userModel);
                echo "</td>";
                echo "<td class='lb_result text-right'><b>$userScoreFormatted</b></td>";
                echo "<td class='lb_date text-right smalldate'><b>$userSubmitAtNice</b></td>";
                echo "</tr>";

                $localUserFound = true;
            }
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

        if ($totalEntries > $count) {
            // Max number fetched, i.e. there are more. Can goto next 20.
            $nextOffset = $offset + $count;
            echo "<a class='btn btn-link' href='/leaderboardinfo.php?i=$lbID&amp;o=$nextOffset&amp;c=$count&amp;f=$friendsOnly'>Next $count &gt;</a>";
        }
        echo "</div>";

        // Render article comments
        echo Blade::render("<x-comment.list :articleType=\"\$articleType\" :articleId=\"\$articleId\" />",
            ['articleType' => ArticleType::Leaderboard, 'articleId' => $leaderboard->id]
        );

        RenderLinkToGameForum($gameTitle, $gameID, $forumTopicID, $permissions);
        echo "<br><br>";
        ?>
    </div>

    <x-slot name="sidebar">
        <x-game.leaderboards-listing :game="$leaderboard->game" />
    </x-slot>
</x-app-layout>

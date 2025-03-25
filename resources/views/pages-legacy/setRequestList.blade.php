<?php

// TODO split

use App\Community\Enums\ClaimStatus;
use App\Community\Enums\UserGameListType;
use App\Models\UserGameListEntry;
use App\Models\User;
use App\Models\System;
use Illuminate\Support\Facades\DB;

if (!authenticateFromCookie($user, $permissions, $userDetails)) {
    abort(401);
}

$maxCount = 50;
$offset = 0;

$username = requestInputSanitized('u');
$selectedConsoleId = (int) request()->input('s');
$selectedRequestStatus = (int) request()->input('x');
$count = (int) request()->input('c', $maxCount);
$offset = (int) request()->input('o', $offset);
$flag = (int) request()->input('f', 0); // 0 - display only active user set requests, else display all user set requests
if ($offset < 0) {
    $offset = 0;
}

$consoles = System::orderBy('Name')->get(['ID', 'Name']);

/** @var ?System $selectedConsole */
$selectedConsole = $consoles->firstWhere('ID', $selectedConsoleId);

$totalRequestedGames = null;
$userSetRequestInformation = null;
if (empty($username)) {
    if ($selectedConsoleId == null) {
        $validConsoles = [];
        foreach ($consoles as $console) {
            if (isValidConsoleID($console['ID'])) {
                $validConsoles[] = $console['ID'];
            }
        }
        $setRequestList = getMostRequestedSetsList($validConsoles, $offset, $count, $selectedRequestStatus);
        $totalRequestedGames = getGamesWithRequests($validConsoles, $selectedRequestStatus);
    } elseif ($selectedConsoleId == -1) {
        $setRequestList = getMostRequestedSetsList(null, $offset, $count, $selectedRequestStatus);
        $totalRequestedGames = getGamesWithRequests(null, $selectedRequestStatus);
    } else {
        $setRequestList = getMostRequestedSetsList($selectedConsoleId, $offset, $count, $selectedRequestStatus);
        $totalRequestedGames = getGamesWithRequests($selectedConsoleId, $selectedRequestStatus);
    }
} else {
    $userModel = User::whereName($username)->first();
    if (!$userModel) {
        abort(404);
    }
    $username = $userModel->display_name;
    $userSetRequestInformation = getUserRequestsInformation($userModel);

    $setRequestList = UserGameListEntry::where('SetRequest.user_id', $userModel->id)
        ->where('type', UserGameListType::AchievementSetRequest)
        ->join('GameData', 'GameData.ID', '=', 'GameId')
        ->join('Console', 'Console.ID', '=', 'GameData.ConsoleID')
        ->leftJoin('SetClaim', function ($join) {
            $join->on('SetClaim.game_id', '=', 'GameData.ID')
                ->whereIn('SetClaim.Status', [ClaimStatus::Active, ClaimStatus::InReview]);
        })
        ->leftJoin('UserAccounts as ua', 'ua.ID', '=', 'SetClaim.user_id')
        ->select([
            'GameData.ID AS GameID',
            'GameData.Title AS GameTitle',
            'GameData.ImageIcon AS GameIcon',
            'Console.Name AS ConsoleName',
            'GameData.achievements_published AS AchievementCount',
            DB::raw('GROUP_CONCAT(DISTINCT(ua.User)) AS Claims'),
        ])
        ->groupBy('GameData.ID')
        ->orderBy(DB::raw(ifStatement("GameData.Title LIKE '~%'", 1, 0) . ", GameData.Title"))
        ->get();
}
?>
<x-app-layout pageTitle="Set Requests">
    <?php
    $gameCounter = 0;

    if ($username === null) {
        echo "<h2>";
        if ($selectedConsoleId > 0) {
            echo "Most Requested " . $selectedConsole->Name . " Sets";
        } else {
            echo "Most Requested Sets";
        }
        echo "</h2>";

        echo "<div class='mb-4'>";
        ?>
        <x-request-list.meta-panel
            :consoles="$consoles"
            :requestedSetsCount="$totalRequestedGames"
            :selectedConsoleId="$selectedConsoleId"
            :selectedRequestStatus="$selectedRequestStatus"
        />
        <?php
        echo "</div>";

        echo "<div class='table-wrapper'><table class='table-highlight'><tbody>";

        // Create table headers
        echo "<tr class='do-not-highlight'>";
        echo "<th class='xl:w-[70%]'>Game</th>";
        echo "<th>Claimed By</th>";
        echo "<th class='text-right'>Requests</th>";
        echo "</tr>";

        // Loop through each set request and display its information
        foreach ($setRequestList as $request) {
            echo $gameCounter++ % 2 == 0 ? "<tr>" : "<tr class=\"alt\">";

            echo "<td class='py-2.5 xl:w-[70%]'>";
            ?>
                <x-game.multiline-avatar
                    :gameId="$request['GameID']"
                    :gameTitle="$request['GameTitle']"
                    :gameImageIcon="$request['GameIcon']"
                    :consoleName="$request['ConsoleName']"
                />
            <?php
            echo "</td><td>";
            $claims = explode(',', $request['Claims']);
            foreach ($claims as $devClaim) {
                echo userAvatar($devClaim);
                echo "</br>";
            }
            echo "</td>";
            echo "<td class='text-right'><a href='/setRequestors.php?g=" . $request['GameID'] . "'>" . $request['Requests'] . "</a></td>";
        }
        echo "</tbody></table></div>";

        // Add page traversal links
        echo "<div class='float-right'>";
        $requestStatusParam = "&x=" . $selectedRequestStatus;
        if ($offset > 0) {
            $prevOffset = $offset - $maxCount;
            if (!empty($selectedConsoleId)) {
                echo "<a href='/setRequestList.php?s=$selectedConsoleId$requestStatusParam'>First</a> - ";
                echo "<a href='/setRequestList.php?o=$prevOffset&s=$selectedConsoleId$requestStatusParam'>&lt; Previous $maxCount</a> - ";
            } else {
                echo "<a href='/setRequestList.php?$requestStatusParam'>First</a> - ";
                echo "<a href='/setRequestList.php?o=$prevOffset$requestStatusParam'>&lt; Previous $maxCount</a> - ";
            }
        }
        if ($gameCounter == $maxCount && $offset != $totalRequestedGames - $maxCount) {
            $nextOffset = $offset + $maxCount;
            if (!empty($selectedConsoleId)) {
                echo "<a href='/setRequestList.php?o=$nextOffset&s=$selectedConsoleId$requestStatusParam'>Next $maxCount &gt;</a>";
                echo " - <a href='/setRequestList.php?o=" . ($totalRequestedGames - $maxCount) . "&s=$selectedConsoleId$requestStatusParam'>Last</a>";
            } else {
                echo "<a href='/setRequestList.php?o=$nextOffset$requestStatusParam'>Next $maxCount &gt;</a>";
                echo " - <a href='/setRequestList.php?o=" . ($totalRequestedGames - $maxCount) . "$requestStatusParam'>Last</a>";
            }
        }
        echo "</div>";
    } else {
        // Looking at the sets a specific user has requested
        echo "<h2>$username's Requested Sets - "
            . $userSetRequestInformation['used'] . " of " . $userSetRequestInformation['total'] . " Requests Made</h2>";

        if ($flag == 0) {
            if ($username === $userDetails['display_name']) {
                echo "<div class='float-right'>Next request in " . localized_number($userSetRequestInformation['pointsForNext']) . " points</div>";
            }
            echo "<a href='/setRequestList.php?u=$username&f=1'>View All User Set Requests</a>";
        } else {
            echo "<a href='/setRequestList.php?u=$username'>View Active User Set Requests</a>";
        }
        echo "<br>";
        echo "<br>";

        echo "<div class='table-wrapper'><table class='table-highlight'><tbody>";

        // Create table headers
        echo "<tr class='do-not-highlight'>";
        echo "<th>Game</th>";
        echo "<th>Claimed By</th>";
        echo "</tr>";

        // Loop through each set request and display them if they do not have any achievements
        foreach ($setRequestList as $request) {
            $setExists = $request['AchievementCount'] > 0;
            if ($flag == 0 && $setExists) {
                continue;
            }

            echo $gameCounter++ % 2 == 0 ? "<tr>" : "<tr class=\"alt\">";

            echo "<td class='py-2.5'>";
            ?>
                <x-game.multiline-avatar
                    :gameId="$request['GameID']"
                    :gameTitle="$request['GameTitle']"
                    :gameImageIcon="$request['GameIcon']"
                    :consoleName="$request['ConsoleName']"
                />
            <?php
            echo "</td>";

            echo "<td>";
            if (!$setExists) {
                $claims = explode(',', $request['Claims']);
                foreach ($claims as $devClaim) {
                    echo userAvatar($devClaim);
                    echo "</br>";
                }
            } else {
                echo "Set Exists";
            }
            echo "</td>";
        }
        echo "</tbody></table></div>";
    }
    ?>
</x-app-layout>

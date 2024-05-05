<?php

use App\Community\Enums\AwardType;
use App\Platform\Enums\UnlockMode;
use Illuminate\Support\Facades\Auth;

authenticateFromCookie($user, $permissions);

$userModel = Auth::user();

$maxCount = 25;
$minDate = '2013-03-02';

$offset = requestInputSanitized('o', 0, 'integer');
$offset = max($offset, 0);
$followed = requestInputSanitized('f', 0, 'integer');
$date = requestInputSanitized('d', date("Y-m-d"));
$awardType = requestInputSanitized('t');
$unlockMode = requestInputSanitized('m');

if ($unlockMode === 's') {
    $unlockMode = UnlockMode::Softcore;
} elseif ($unlockMode === 'h') {
    $unlockMode = UnlockMode::Hardcore;
}

$lbUsers = $followed === 1 ? 'Followed Users' : '';

if ($awardType != AwardType::GameBeaten && $awardType != AwardType::Mastery) {
    $awardType = null;
}
if ($unlockMode != UnlockMode::Hardcore && $unlockMode != UnlockMode::Softcore) {
    $unlockMode = null;
}

// Which award type filter should we default to?
$awardTypes = [
    AwardType::Mastery => ['mastered', 'completed'],
    AwardType::GameBeaten => ['beaten-hardcore', 'beaten-softcore'],
];
$index = $unlockMode == UnlockMode::Hardcore ? 0 : 1;
$selectedAwardType = $awardTypes[$awardType][$index] ?? null;

if ($followed == 1) {
    $data = getRecentProgressionAwardData($date, $user, $offset, $maxCount + 1, $awardType, $unlockMode);
} else {
    $data = getRecentProgressionAwardData($date, null, $offset, $maxCount + 1, $awardType, $unlockMode);
}
?>
<x-app-layout pageTitle="Recent {{ $lbUsers }} Game Awards">
    <h2>Recent {{ $lbUsers }} Game Awards</h2>
    <x-recent-awards.meta-panel
        :minAllowedDate="$minDate"
        :selectedAwardType="$selectedAwardType"
        :selectedDate="$date"
        :selectedUsers="$followed == 1 ? 'followed' : 'all'"
    />
    <?php
    echo "<table class='table-highlight'><tbody>";

    // Headers
    echo "<tr class='do-not-highlight'>";
    echo "<th>User</th>";
    echo "<th>Type</th>";
    echo "<th>Game</th>";
    echo "<th>Date</th>";
    echo "</tr>";

    $userCount = 0;
    $skip = false;
    // Create the table rows
    foreach ($data as $dataPoint) {
        // Break if we have hit the maxCount + 1 user
        if ($userCount == $maxCount) {
            $userCount++;
            $skip = true;
        }

        if (!$skip) {
            echo "<tr>";

            echo "<td class='py-2.5'>";
            echo userAvatar($dataPoint['User']);
            echo "</td>";

            echo "<td>";
            if ($dataPoint['AwardType'] == AwardType::Mastery) {
                if ($dataPoint['AwardDataExtra'] == 1) {
                    echo "Mastered";
                } else {
                    echo "Completed";
                }
            } elseif ($dataPoint['AwardType'] == AwardType::GameBeaten) {
                if ($dataPoint['AwardDataExtra'] == 1) {
                    echo "Beaten";
                } else {
                    echo "Beaten (softcore)";
                }
            }
            echo "</td>";

            echo "<td>";
            ?>
                <x-game.multiline-avatar
                    :gameId="$dataPoint['GameID']"
                    :gameTitle="$dataPoint['GameTitle']"
                    :gameImageIcon="$dataPoint['GameIcon']"
                    :consoleName="$dataPoint['ConsoleName']"
                />
            <?php
            echo "</td>";

            echo "<td>";
            echo $dataPoint['AwardedAt'];
            echo "</td>";

            echo "</tr>";
            $userCount++;
        }
    }
    echo "</tbody></table>";

    // Add page traversal
    echo "<div class='text-right'>";
    if ($date > $minDate) {
        $prevDate = date('Y-m-d', strtotime($date . "-1 days"));
        echo "<a href='/recentMastery.php?d=$prevDate&f=$followed&o=0&t=$awardType&m=$unlockMode'>&lt; Prev Day </a>";
        if ($date < date("Y-m-d")) {
            echo " | ";
        }
    }
    if ($offset > 0) {
        $prevOffset = $offset - $maxCount;
        echo "<a href='/recentMastery.php?d=$date&f=$followed&o=$prevOffset&t=$awardType&m=$unlockMode'>&lt; Prev $maxCount </a>";
    }
    if ($userCount > $maxCount) {
        if ($offset > 0) {
            echo " - ";
        }
        $nextOffset = $offset + $maxCount;
        echo "<a href='/recentMastery.php?d=$date&f=$followed&o=$nextOffset&t=$awardType&m=$unlockMode'>Next $maxCount &gt;</a>";
    }
    if ($date < date("Y-m-d")) {
        $nextDate = date('Y-m-d', strtotime($date . "+1 days"));
        echo " | <a href='/recentMastery.php?d=$nextDate&f=$followed&o=0&t=$awardType&m=$unlockMode'>Next Day &gt;</a>";
    }
    echo "</div>";
    ?>
</x-app-layout>

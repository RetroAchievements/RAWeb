<?php

use App\Platform\Actions\GetGlobalRankingDataAction;
use App\Platform\Enums\GlobalRankingMode;
use App\Platform\Enums\GlobalRankingSortField;
use App\Platform\Enums\GlobalRankingWindow;
use App\Platform\Enums\UnlockMode;
use Illuminate\Support\Facades\Auth;

authenticateFromCookie($user, $permissions, $userDetails);

$userModel = Auth::user();

$maxCount = 25;

$offset = requestInputSanitized('o', 0, 'integer');
$offset = max($offset, 0);
$sort = requestInputSanitized('s', 5, 'integer');
$type = requestInputSanitized('t', 2, 'integer');
$friends = requestInputSanitized('f', 0, 'integer');
$date = requestInputSanitized('d', date("Y-m-d"));
$dateUnix = strtotime("$date");

switch ($type) {
    case 0: // Daily
        $lbType = "Daily";
        break;
    case 1: // Weekly
        $lbType = "Weekly";
        break;
    case 2: // All Time
        $lbType = "All Time";

        // Set default sorting if the user switches to All Time with an invalid All Time sorting selected.
        if (($sort % 10) == 8 || ($sort % 10) == 9) {
            $sort = 5;
        }
        break;
    default:
        $lbType = "";
        break;
}

$lbUsers = match ($friends) {
    0 => "Global",
    1 => "Followed Users",
    default => "",
};

$rankingWindow = match ($type) {
    1 => GlobalRankingWindow::Weekly,
    2 => GlobalRankingWindow::AllTime,
    default => GlobalRankingWindow::Daily,
};

$rankingDescending = $sort < 10;
$rankingSortId = $rankingDescending ? $sort : $sort - 10;
if ($rankingSortId === 7 || ($rankingWindow === GlobalRankingWindow::AllTime && in_array($rankingSortId, [8, 9], true))) {
    $rankingSortId = 5;
    $rankingDescending = true;
}

$rankingMode = in_array($rankingSortId, [2, 3, 8], true)
    ? GlobalRankingMode::Casual
    : GlobalRankingMode::Hardcore;
$rankingSortField = match ($rankingSortId) {
    3, 4 => GlobalRankingSortField::AchievementsUnlocked,
    6 => GlobalRankingSortField::WeightedPoints,
    8, 9 => GlobalRankingSortField::AwardsCount,
    default => GlobalRankingSortField::Points,
};

$getGlobalRankingData = new GetGlobalRankingDataAction();

if ($friends == 1) {
    // We do a maxCount + 1 so that if we get maxCount + 1 rows returned we know
    // there are more row to get and we can add a "Next X" link for page traversal
    $data = $getGlobalRankingData->execute(
        isDescending: $rankingDescending,
        followedByUser: $userModel,
        limit: getFriendCount($userModel) + 1,
        mode: $rankingMode,
        sortBy: $rankingSortField,
        window: $rankingWindow,
    );
} else {
    $data = $getGlobalRankingData->execute(
        isDescending: $rankingDescending,
        limit: $maxCount + 1,
        mode: $rankingMode,
        offset: $offset,
        sortBy: $rankingSortField,
        window: $rankingWindow,
    );
}

$unlockMode = $rankingMode === GlobalRankingMode::Casual ? UnlockMode::Casual : UnlockMode::Hardcore;
?>
<x-app-layout pageTitle="{{ $lbUsers }} Ranking - {{ $lbType }}">
    <?php
    echo "<h2>" . $lbUsers . " Ranking - " . $lbType . "</h2>";

    // Add the leaderboard filters
    echo "<div class='embedded mb-1'>";

    // Create the Leaderboard Type filters
    echo "<div>";
    echo "<b>Leaderboard Type:</b> ";
    if ($type == 0) {
        echo "<b><a href='/globalRanking.php?s=$sort&t=0&d=$date&f=$friends'>*Daily</a></b> | ";
    } else {
        echo "<a href='/globalRanking.php?s=$sort&t=0&d=$date&f=$friends'>Daily</a> | ";
    }
    if ($type == 1) {
        echo "<b><a href='/globalRanking.php?s=$sort&t=1&d=$date&f=$friends'>*Weekly</a></b> | ";
    } else {
        echo "<a href='/globalRanking.php?s=$sort&t=1&d=$date&f=$friends'>Weekly</a> | ";
    }
    if ($type == 2) {
        echo "<b><a href='/globalRanking.php?s=$sort&t=2&d=$date&f=$friends'>*All Time</a></b>";
    } else {
        echo "<a href='/globalRanking.php?s=$sort&t=2&d=$date&f=$friends'>All Time</a>";
    }
    echo "</div>";

    // Create the Users filters only if a user is logged in
    if ($user !== null) {
        echo "<div>";
        echo "<b>Users:</b> ";
        if ($friends == 0) {
            echo "<b><a href='/globalRanking.php?s=$sort&t=$type&d=$date&f=0'>*All Users</a></b> | ";
        } else {
            echo "<a href='/globalRanking.php?s=$sort&t=$type&d=$date&f=0'>All Users</a> | ";
        }
        if ($friends == 1) {
            echo "<b><a href='/globalRanking.php?s=$sort&t=$type&d=$date&f=1'>*Followed Users</a></b>";
        } else {
            echo "<a href='/globalRanking.php?s=$sort&t=$type&d=$date&f=1'>Followed Users</a>";
        }
        echo "</div>";
    }

    // Create the hardcore filter
    echo "<div>";
    echo "<b>Mode:</b> ";
    if ($unlockMode == UnlockMode::Hardcore) {
        echo "<b><a href='/globalRanking.php?s=5&t=$type&d=$date&f=$friends'>*Hardcore</a></b> | ";
    } else {
        echo "<a href='/globalRanking.php?s=5&t=$type&d=$date&f=$friends'>Hardcore</a> | ";
    }
    if ($unlockMode == UnlockMode::Casual) {
        echo "<b><a href='/globalRanking.php?s=2&t=$type&d=$date&f=$friends'>*Casual</a></b>";
    } else {
        echo "<a href='/globalRanking.php?s=2&t=$type&d=$date&f=$friends'>Casual</a>";
    }
    echo "</div>";

    // Clear filter
    if (($sort != 5 && $sort != 2) || $type != 0 || $friends != 0) {
        echo "<div>";
        if ($sort == 2) {
            echo "<a href='/globalRanking.php?s=2'>Clear Filter</a>";
        } else {
            echo "<a href='/globalRanking.php'>Clear Filter</a>";
        }
        echo "</div>";
    }
    echo "</div>";

    echo "<div class='table-wrapper'>";
    echo "<table class='table-highlight'><tbody>";

    echo "<tr class='do-not-highlight'>";

    // Only show the rank when we actually know the rank
    if ($sort < 10 && $sort % 10 != 1) {
        echo "<th>Rank</th>";
    }

    $sortFilter = function ($label, $sortValue, $descending = true) use ($sort, $type, $date, $friends) {
        if (($sort % 10) == $sortValue) {
            if ($sort == $sortValue) {
                $sortValue += 10;
                $arrow = $descending ? ' &#9660;' : ' &#9650;';
            } else {
                $arrow = $descending ? ' &#9650;' : ' &#9660;';
            }
            echo "<b><a href='/globalRanking.php?s=$sortValue&t=$type&d=$date&f=$friends'>$label$arrow</a></b>";
        } else {
            echo "<a href='/globalRanking.php?s=$sortValue&t=$type&d=$date&f=$friends'>$label</a>";
        }
    };

    // User header
    echo "<th>User</th>";

    // Sortable Achievements header
    echo "<th class='text-right'>";
    if ($unlockMode == UnlockMode::Hardcore) {
        $sortFilter('Hardcore Unlocks', 4);
    } else {
        $sortFilter('Casual Unlocks', 3);
    }
    echo "</th>";

    // Sortable Points header
    echo "<th class='text-right'>";
    if ($unlockMode == UnlockMode::Hardcore) {
        $sortFilter('Hardcore Points', 5);
        $sortFilter(' (RetroPoints)', 6);
    } else {
        $sortFilter('Casual Points', 2);
    }
    echo "</th>";

    // Sortable RetroRatio header
    if ($unlockMode == UnlockMode::Hardcore) {
        echo "<th class='text-right'>";
        echo 'RetroRatio';
        echo "</th>";
    }

    // Sortable Mastered Awards header
    echo "<th class='text-right'>";
    if ($unlockMode == UnlockMode::Hardcore) {
        if ($type == 2) { // Disable sorting if All Time
            echo "Mastered";
        } else {
            $sortFilter('Mastered', 9);
        }
    } else {
        if ($type == 2) { // Disable sorting if All Time
            echo "Completed";
        } else {
            $sortFilter('Completed', 8);
        }
    }

    echo "</tr>";

    // Create the table rows
    $userListed = false;
    $userRank = 0;
    $findUserRank = false;
    if ($friends == 1) {
        $rank = 1;
    } else {
        $rank = $offset + 1;
    }
    $rowRank = $rank;

    // Determine which field to use for the rank comparison.
    // This ensures ties are properly handled.
    $rankField = match ($rankingSortField) {
        GlobalRankingSortField::Points => 'points',
        GlobalRankingSortField::WeightedPoints => 'weightedPoints',
        GlobalRankingSortField::AchievementsUnlocked => 'achievementsUnlocked',
        GlobalRankingSortField::AwardsCount => 'awardsCount',
    };
    $rankValue = null;

    $userCount = 0;
    foreach ($data as $dataPoint) {
        // Break if we have hit the maxCount + 1 user
        if ($userCount == $maxCount) {
            $userCount++;
            $findUserRank = true;
        }

        if ($dataPoint['rankNumber'] !== null) {
            $rank = $dataPoint['rankNumber'];
        } elseif ($dataPoint[$rankField] != $rankValue) {
            $rank = $rowRank;
            $rankValue = $dataPoint[$rankField];
        }

        if ($rowRank < $offset + 1 || $findUserRank) {
            if ($dataPoint['username'] == $user) {
                $userRank = $rank;
            }
            $rowRank++;
        } else {
            // Outline the currently logged in user in the table
            if ($dataPoint['username'] == $user) {
                $userListed = true;
                echo "<tr style='outline: thin solid'>";
            } else {
                echo "<tr>";
            }

            // Only show the rank when we actually know the rank
            if ($sort < 10 && $sort % 10 != 1) {
                echo "<td>" . localized_number($rank) . "</td>";
            }
            echo "<td>";
            echo userAvatar([
                'username' => $dataPoint['username'],
                'display_name' => $dataPoint['displayName'],
            ], iconClass: 'mr-1');
            echo "</td>";

            // If viewing the daily leaderboard then link the total achievements obtained to the users history page for the day
            if ($type == 0) {
                echo "<td class='text-right'><a href='historyexamine.php?d=$dateUnix&u=" . $dataPoint['displayName'] . "'>" . localized_number($dataPoint['achievementsUnlocked']) . "</a></td>";
            } else {
                echo "<td class='text-right'>" . localized_number($dataPoint['achievementsUnlocked'] ?? 0) . "</td>";
            }

            if ($unlockMode == UnlockMode::Hardcore) {
                echo "<td class='text-right'>" . localized_number($dataPoint['points']);
                ?>
                <x-points-weighted-container>({{ localized_number($dataPoint['weightedPoints']) }})</x-points-weighted-container>
                <?php
                echo "</td>";
                if ($dataPoint['points'] == 0) {
                    echo "<td class='text-right'>0.00</td>";
                } else {
                    echo "<td class='text-right'>" . $dataPoint['retroRatio'] . "</td>";
                }
            } else {
                echo "<td class='text-right'>" . localized_number($dataPoint['points']) . "</td>";
            }

            echo "<td class='text-right'>" . localized_number($dataPoint['awardsCount'] ?? 0) . "</td></tr>";

            $rowRank++;
            $userCount++;
        }
    }

    // Display the user if they are not in the list
    if ($userModel !== null && !$userListed) {
        // Get and display the information for the logged in user if applicable
        $userData = $getGlobalRankingData->execute(
            isDescending: $rankingDescending,
            limit: 1,
            mode: $rankingMode,
            sortBy: $rankingSortField,
            user: $userModel,
            window: $rankingWindow,
        );
        if (!empty($userData)) {
            // Add dummy row to separate the user from the rest of the table
            echo "<tr class='do-not-highlight'><td colspan='7'>&nbsp;</td></tr>";
            echo "<tr style='outline: thin solid'>";

            if ($sort < 10 && $sort % 10 != 1) {
                $rank = $userData[0]['rankNumber'] ?? ($friends == 1 ? $userRank : null);
                echo "<td>" . ($rank !== null ? localized_number($rank) : '') . "</td>";
            }
            echo "<td>";
            echo userAvatar([
                'username' => $userData[0]['username'],
                'display_name' => $userData[0]['displayName'],
            ], iconClass: 'mr-1');
            echo "</td>";

            // If viewing the daily leaderboard then link the total achievements obtained to the users history page for the day
            if ($type == 0) {
                echo "<td class='text-right'><a href='historyexamine.php?d=$dateUnix&u=" . $userData[0]['displayName'] . "'>" . $userData[0]['achievementsUnlocked'] . "</a></td>";
            } else {
                echo "<td class='text-right'>" . localized_number($userData[0]['achievementsUnlocked']) . "</a></td>";
            }

            if ($unlockMode == UnlockMode::Hardcore) {
                echo "<td class='text-right'>" . localized_number($userData[0]['points']);
                ?>
                <x-points-weighted-container>({{ localized_number($userData[0]['weightedPoints']) }})</x-points-weighted-container>
                <?php
                echo "</td>";
                if ($userData[0]['points'] == 0) {
                    echo "<td class='text-right'>0.00</td>";
                } else {
                    echo "<td class='text-right'>" . $userData[0]['retroRatio'] . "</td>";
                }
            } else {
                echo "<td class='text-right'>" . localized_number($userData[0]['points']) . "</td>";
            }

            echo "<td class='text-right'>" . localized_number($userData[0]['awardsCount'] ?? 0) . "</td></tr>";
        }
    }
    echo "</tbody></table>";
    echo "</div>";

    // Add page traversal
    echo "<div class='text-right mt-2'>";
    if ($offset > 0) {
        $prevOffset = $offset - $maxCount;
        echo "<a href='/globalRanking.php?s=$sort&t=$type&d=$date&f=$friends'>First</a> - ";
        echo "<a href='/globalRanking.php?s=$sort&t=$type&d=$date&f=$friends&o=$prevOffset'>&lt; Previous $maxCount</a> - ";
    }
    if ($userCount > $maxCount) {
        $nextOffset = $offset + $maxCount;
        echo "<a href='/globalRanking.php?s=$sort&t=$type&d=$date&f=$friends&o=$nextOffset'>Next $maxCount &gt;</a>";
    }
    echo "</div>";
    ?>
</x-app-layout>

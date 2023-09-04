<?php

use App\Community\Enums\RankType;
use App\Platform\Enums\UnlockMode;
use Illuminate\Support\Facades\Blade;

authenticateFromCookie($user, $permissions, $userDetails);

$maxCount = 25;

$offset = requestInputSanitized('o', 0, 'integer');
$offset = max($offset, 0);
$sort = requestInputSanitized('s', 5, 'integer');
$type = requestInputSanitized('t', 2, 'integer');
$friends = requestInputSanitized('f', 0, 'integer');
$untracked = requestInputSanitized('u', 0, 'integer');
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

if ($friends == 1) {
    // We do a maxCount + 1 so that if we get maxCount + 1 rows returned we know
    // there are more row to get and we can add a "Next X" link for page traversal
    $data = getGlobalRankingData($type, $sort, $date, null, $user, $untracked, 0, getFriendCount($user) + 1, 0);
} else {
    $data = getGlobalRankingData($type, $sort, $date, null, null, $untracked, $offset, $maxCount + 1, 0);
}

$unlockMode = match ($sort % 10) {
    2 => UnlockMode::Softcore, // Points
    3 => UnlockMode::Softcore, // Achievements
    8 => UnlockMode::Softcore, // Awards
    default => UnlockMode::Hardcore,
};

RenderContentStart($lbUsers . " Ranking - " . $lbType);
?>
<article>
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
    if ($unlockMode == UnlockMode::Softcore) {
        echo "<b><a href='/globalRanking.php?s=2&t=$type&d=$date&f=$friends'>*Softcore</a></b>";
    } else {
        echo "<a href='/globalRanking.php?s=2&t=$type&d=$date&f=$friends'>Softcore</a>";
    }
    echo "</div>";

    // Create the custom date folter
    echo "<form action='/globalRanking.php'>";
    echo "<label for='d'><b>Custom Date: </b></label>";
    echo "<input type='hidden' name='s' value=" . $sort . ">";
    echo "<input type='hidden' name='t' value=" . $type . ">";
    echo "<input type='date' name='d' value=" . $date . " min='2012-01-01' max=" . date("Y-m-d") . "> ";
    echo "<input type='hidden' name='f' value=" . $friends . ">";
    echo "<button class='btn'>Go to Date</button>";
    echo "</form>";

    // Clear filter
    if (($sort != 5 && $sort != 2) || $type != 0 || $date != date("Y-m-d") || $friends != 0) {
        echo "<div>";
        if ($sort == 2) {
            echo "<a href='/globalRanking.php?s=2'>Clear Filter</a>";
        } else {
            echo "<a href='/globalRanking.php'>Clear Filter</a>";
        }
        echo "</div>";
    }
    echo "</div>";

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
        $sortFilter('Hardcore Achievements', 4);
    } else {
        $sortFilter('Softcore Achievements', 3);
    }
    echo "</th>";

    // Sortable Points header
    echo "<th class='text-right'>";
    if ($unlockMode == UnlockMode::Hardcore) {
        $sortFilter('Hardcore Points', 5);
        $sortFilter(' (RetroPoints)', 6);
    } else {
        $sortFilter('Softcore Points', 2);
    }
    echo "</th>";

    // Sortable Retro Ratio header
    if ($unlockMode == UnlockMode::Hardcore) {
        echo "<th class='text-right'>";
        $sortFilter('Retro Ratio', 7);
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
    $rankPoints = null;
    $userCount = 0;
    foreach ($data as $dataPoint) {
        // Break if we have hit the maxCount + 1 user
        if ($userCount == $maxCount) {
            $userCount++;
            $findUserRank = true;
        }

        if ($dataPoint['Points'] != $rankPoints) {
            if ($rankPoints === null && $friends === 0 && $type === 2 && $offset > 0) {
                // first rank of subsequent pages for all users / all time should be calculated
                // as it might be shared with users on the previous page
                $rankType = ($unlockMode == UnlockMode::Hardcore) ? RankType::Hardcore : RankType::Softcore;
                $rank = getUserRank($dataPoint['User'], $rankType);
            } else {
                $rank = $rowRank;
            }

            $rankPoints = $dataPoint['Points'];
        }

        if ($rowRank < $offset + 1 || $findUserRank) {
            if ($dataPoint['User'] == $user) {
                $userRank = $rank;
            }
            $rowRank++;
        } else {
            // Outline the currently logged in user in the table
            if ($dataPoint['User'] == $user) {
                $userListed = true;
                echo "<tr style='outline: thin solid'>";
            } else {
                echo "<tr>";
            }

            // Only show the rank when we actually know the rank
            if ($sort < 10 && $sort % 10 != 1) {
                echo "<td>" . $rank . "</td>";
            }
            echo "<td>";
            echo userAvatar($dataPoint['User'], iconClass: 'mr-1');
            echo "</td>";

            // If viewing the daily leaderboard then link the total achievements obtained to the users history page for the day
            if ($type == 0) {
                echo "<td class='text-right'><a href='historyexamine.php?d=$dateUnix&u=" . $dataPoint['User'] . "'>" . localized_number($dataPoint['AchievementCount']) . "</a></td>";
            } else {
                echo "<td class='text-right'>" . localized_number($dataPoint['AchievementCount']) . "</td>";
            }

            if ($unlockMode == UnlockMode::Hardcore) {
                echo "<td class='text-right'>" . localized_number($dataPoint['Points']);
                echo Blade::render("<x-points-weighted-container>(" . localized_number($dataPoint['RetroPoints']) . ")</x-points-weighted-container>");
                echo "</td>";
                if ($dataPoint['Points'] == 0) {
                    echo "<td class='text-right'>0.00</td>";
                } else {
                    echo "<td class='text-right'>" . $dataPoint['RetroRatio'] . "</td>";
                }
            } else {
                echo "<td class='text-right'>" . localized_number($dataPoint['Points']) . "</td>";
            }

            echo "<td class='text-right'>" . localized_number($dataPoint['TotalAwards']) . "</td></tr>";

            $rowRank++;
            $userCount++;
        }
    }

    // Display the user if they are not in the list
    if ($user !== null) {
        if (!$userListed) {
            // Get and display the information for the logged in user if applicable
            $userData = getGlobalRankingData($type, $sort, $date, $user, null, $untracked, 0, 1);
            if (!empty($userData)) {
                // Add dummy row to separate the user from the rest of the table
                echo "<tr class='do-not-highlight'><td colspan='7'>&nbsp;</td></tr>";
                echo "<tr style='outline: thin solid'>";

                // Get the user rank when sorting by points or retro points
                if ($friends == 1) {
                    echo "<td>" . $userRank . "</td>";
                } elseif ($type != 2) {
                    // Dont show rank on pages you are not actually ranked in. This would require rerunning the query just to find yourself
                    echo "<td></td>";
                } else {
                    if ($sort < 10 && $sort % 10 != 1) {
                        if ($sort == 5) {
                            echo "<td>" . getUserRank($user, RankType::Hardcore) . "</td>";
                        } elseif ($sort == 6) {
                            echo "<td>" . getUserRank($user, RankType::TruePoints) . "</td>";
                        } elseif ($sort == 2) {
                            echo "<td>" . getUserRank($user, RankType::Softcore) . "</td>";
                        } else {
                            echo "<td></td>";
                        }
                    }
                }
                echo "<td>";
                echo userAvatar($userData[0]['User'], iconClass: 'mr-1');
                echo "</td>";

                // If viewing the daily leaderboard then link the total achievements obtained to the users history page for the day
                if ($type == 0) {
                    echo "<td class='text-right'><a href='historyexamine.php?d=$dateUnix&u=" . $userData[0]['User'] . "'>" . $userData[0]['AchievementCount'] . "</a></td>";
                } else {
                    echo "<td class='text-right'>" . localized_number($userData[0]['AchievementCount']) . "</a></td>";
                }

                if ($unlockMode == UnlockMode::Hardcore) {
                    echo "<td class='text-right'>" . localized_number($userData[0]['Points']);
                    echo Blade::render("<x-points-weighted-container>(" . localized_number($userData[0]['RetroPoints']) . ")</x-points-weighted-container>");
                    echo "</td>";
                    if ($userData[0]['Points'] == 0) {
                        echo "<td class='text-right'>0.00</td>";
                    } else {
                        echo "<td class='text-right'>" . $userData[0]['RetroRatio'] . "</td>";
                    }
                } else {
                    echo "<td class='text-right'>" . localized_number($userData[0]['Points']) . "</td>";
                }

                echo "<td class='text-right'>" . localized_number($userData[0]['TotalAwards']) . "</td></tr>";
            }
        }
    }
    echo "</tbody></table>";

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
</article>
<?php RenderContentEnd(); ?>

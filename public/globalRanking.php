<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../lib/bootstrap.php';

RA_ReadCookieCredentials($user, $points, $truePoints, $unreadMessageCount, $permissions);

$maxCount = 25;

$errorCode = requestInputSanitized('e');
$offset = requestInputSanitized('o', 0, 'integer');
$offset = max($offset, 0);
$sort = requestInputSanitized('s', 5, 'integer');
$type = requestInputSanitized('t', 0, 'integer');
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
        if (($sort % 10) != 5 && ($sort % 10) != 6 && ($sort % 10) != 7) {
            $sort = 5;
        }
        break;
    default:
        $lbType = "";
        break;
}

switch ($friends) {
    case 0: // Global
        $lbUsers = "Global";
        break;
    case 1: // Friends
        $lbUsers = "Friends";
        break;
    default:
        $lbUsers = "";
        break;
}

if ($friends == 1) {
    // We do a maxCount + 1 so that if we get maxCount + 1 rows returned we know
    // there are more row to get and we can add a "Next X" link for page traversal
    $data = getGlobalRankingData($type, $sort, $date, null, $user, $untracked, $offset, getFriendCount($user), 0);
} else {
    $data = getGlobalRankingData($type, $sort, $date, null, null, $untracked, $offset, $maxCount + 1, 0);
}

RenderHtmlStart();
RenderHtmlHead($lbUsers . " Ranking - " . $lbType);
?>
<body>
<?php
RenderTitleBar($user, $points, $truePoints, $unreadMessageCount, $errorCode, $permissions);
RenderToolbar($user, $permissions);
?>
<div id='mainpage'>
    <div id='fullcontainer'>
        <?php
        RenderErrorCodeWarning($errorCode);
        echo "<h2 class='longheader'>" . $lbUsers . " Ranking - " . $lbType . "</h2>";

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
                echo "<b><a href='/globalRanking.php?s=$sort&t=$type&d=$date&f=1'>*Friends Only</a></b>";
            } else {
                echo "<a href='/globalRanking.php?s=$sort&t=$type&d=$date&f=1'>Friends Only</a>";
            }
            echo "</div>";
        }

        // Create the custom date folter
        echo "<form action='/globalRanking.php' method='get'>";
        echo "<label for='d'><b>Custom Date: </b></label>";
        echo "<input type='hidden' name='s' value=" . $sort . ">";
        echo "<input type='hidden' name='t' value=" . $type . ">";
        echo "<input type='date' name='d' value=" . $date . " min='2012-01-01' max=" . date("Y-m-d") . "> ";
        echo "<input type='hidden' name='f' value=" . $friends . ">";
        echo "<input type='submit' value='Goto Date' />";
        echo "</form>";

        // Clear filter
        if ($sort != 5 || $type != 0 || $date != date("Y-m-d") || $friends != 0) {
            echo "<div>";
            echo "<a href='/globalRanking.php'>Clear Filter</a>";
            echo "</div>";
        }
        echo "</div>";

        // Toggle ascending or descending sorting
        $sort2 = ($sort == 2) ? 12 : 2; // Total Achievement
        $sort3 = ($sort == 3) ? 13 : 3; // Softcore Achievements
        $sort4 = ($sort == 4) ? 14 : 4; // Hardcore Achievements
        $sort5 = ($sort == 5) ? 15 : 5; // Points
        $sort6 = ($sort == 6) ? 16 : 6; // Retro Points
        $sort7 = ($sort == 7) ? 17 : 7; // Retro Ratio
        $sort8 = ($sort == 8) ? 18 : 8; // Completed Awards
        $sort9 = ($sort == 9) ? 19 : 9; // Mastered Awards

        echo "<table><tbody>";

        // Only show the rank when we actually know the rank
        if ($sort < 10 && ($sort % 10) != 1) {
            echo "<th>Rank</th>";
        }

        // User header
        echo "<th>User</th>";

        // Sortable Total Achievement header
        if ($type == 2) {
            echo "<th>Achievement Obtained</br>Total";
        } else {
            if (($sort % 10) == 2) {
                if ($sort2 == 2) {
                    echo "<th>Achievement Obtained</br><b><a href='/globalRanking.php?s=$sort2&t=$type&d=$date&f=$friends'>Total &#9650;</a></b>";
                } else {
                    echo "<th>Achievement Obtained</br><b><a href='/globalRanking.php?s=$sort2&t=$type&d=$date&f=$friends'>Total &#9660;</a></b>";
                }
            } else {
                echo "<th>Achievement Obtained</br><a href='/globalRanking.php?s=$sort2&t=$type&d=$date&f=$friends'>Total</a>";
            }
        }

        // Sortable Softcore Achievements header
        if ($type == 2) {
            echo " - SC ";
        } else {
            if (($sort % 10) == 3) {
                if ($sort3 == 3) {
                    echo " - <b><a href='/globalRanking.php?s=$sort3&t=$type&d=$date&f=$friends'>SC &#9650;</a></b> ";
                } else {
                    echo " - <b><a href='/globalRanking.php?s=$sort3&t=$type&d=$date&f=$friends'>SC &#9660;</a></b> ";
                }
            } else {
                echo " - <a href='/globalRanking.php?s=$sort3&t=$type&d=$date&f=$friends'>SC</a></b> ";
            }
        }

        // Sortable Hardcore Achievements header
        if ($type == 2) {
            echo "(HC)</th>";
        } else {
            if (($sort % 10) == 4) {
                if ($sort4 == 4) {
                    echo "<b><a href='/globalRanking.php?s=$sort4&t=$type&d=$date&f=$friends'>(HC) &#9650;</a></b></th>";
                } else {
                    echo "<b><a href='/globalRanking.php?s=$sort4&t=$type&d=$date&f=$friends'>(HC) &#9660;</a></b></th>";
                }
            } else {
                echo "<a href='/globalRanking.php?s=$sort4&t=$type&d=$date&f=$friends'>(HC)</a></th>";
            }
        }

        // Sortable Points header
        if (($sort % 10) == 5) {
            if ($sort5 == 5) {
                echo "<th><b><a href='/globalRanking.php?s=$sort5&t=$type&d=$date&f=$friends'>Points &#9650;</a></b> ";
            } else {
                echo "<th><b><a href='/globalRanking.php?s=$sort5&t=$type&d=$date&f=$friends'>Points &#9660;</a></b> ";
            }
        } else {
            echo "<th><a href='/globalRanking.php?s=$sort5&t=$type&d=$date&f=$friends'>Points</a> ";
        }

        // Sortable Retro Points header
        if (($sort % 10) == 6) {
            if ($sort6 == 6) {
                echo "<b><a href='/globalRanking.php?s=$sort6&t=$type&d=$date&f=$friends'>(Retro Points) &#9650;</a></b></th>";
            } else {
                echo "<b><a href='/globalRanking.php?s=$sort6&t=$type&d=$date&f=$friends'>(Retro Points) &#9660;</a></b></th>";
            }
        } else {
            echo "<a href='/globalRanking.php?s=$sort6&t=$type&d=$date&f=$friends'>(Retro Points)</a></th>";
        }

        // Sortable Retro Ratio header
        if (($sort % 10) == 7) {
            if ($sort7 == 7) {
                echo "<th><b><a href='/globalRanking.php?s=$sort7&t=$type&d=$date&f=$friends'>Retro Ratio &#9650;</a></b></th>";
            } else {
                echo "<th><b><a href='/globalRanking.php?s=$sort7&t=$type&d=$date&f=$friends'>Retro Ratio &#9660;</a></b></th>";
            }
        } else {
            echo "<th><a href='/globalRanking.php?s=$sort7&t=$type&d=$date&f=$friends'>Retro Ratio</a></th>";
        }

        // Sortable Completed Awards header
        if ($type == 2) {
            echo "<th>Site Awards</br> Total - Completed ";
        } else {
            if (($sort % 10) == 8) {
                if ($sort8 == 8) {
                    echo "<th>Site Awards</br> Total - <b><a href='/globalRanking.php?s=$sort8&t=$type&d=$date&f=$friends'>Completed &#9650;</a></b> ";
                } else {
                    echo "<th>Site Awards</br> Total - <b><a href='/globalRanking.php?s=$sort8&t=$type&d=$date&f=$friends'>Completed &#9660;</a></b> ";
                }
            } else {
                echo "<th>Site Awards</br> Total - <a href='/globalRanking.php?s=$sort8&t=$type&d=$date&f=$friends'>Completed</a> ";
            }
        }

        // Sortable Mastered Awards header
        if ($type == 2) {
            echo "(Mastered)</th>";
        } else {
            if (($sort % 10) == 9) {
                if ($sort9 == 9) {
                    echo "<b><a href='/globalRanking.php?s=$sort9&t=$type&d=$date&f=$friends'>(Mastered) &#9650;</a></b></th>";
                } else {
                    echo "<b><a href='/globalRanking.php?s=$sort9&t=$type&d=$date&f=$friends'>(Mastered) &#9660;</a></b></th>";
                }
            } else {
                echo "<a href='/globalRanking.php?s=$sort9&t=$type&d=$date&f=$friends'>(Mastered)</a></th>";
            }
        }

        // Create the table rows
        $userListed = false;
        $userRank = 0;
        $findUserRank = false;
        $rank = $offset + 1;
        $userCount = 0;
        foreach ($data as $dataPoint) {
            // Break if we have hit the maxCount + 1 user
            if ($userCount == $maxCount) {
                $userCount++;
                $findUserRank = true;
            }

            if (!$findUserRank) {
                // Outline the currently logged in user in the table
                if ($dataPoint['User'] == $user) {
                    $userListed = true;
                    echo "<tr style='outline: thin solid'>";
                } else {
                    echo "<tr>";
                }

                // Only show the rank when we actually know the rank
                if ($sort < 10 && ($sort % 10) != 1) {
                    echo "<td>" . $rank . "</td>";
                }
                echo "<td>";
                echo GetUserAndTooltipDiv($dataPoint['User'], true);
                echo GetUserAndTooltipDiv($dataPoint['User'], false);
                echo "</td>";

                // If viewing the daily leaderboard then link the total achievements obtained to the users history page for the day
                if ($type == 0) {
                    echo "<td><a href='historyexamine.php?d=$dateUnix&u=" . $dataPoint['User'] . "'>" . $dataPoint['AchievementsObtained'] . "</a> - " . $dataPoint['SoftcoreCount'] . " (" . $dataPoint['HardcoreCount'] . ")</td>";
                } else {
                    echo "<td>" . $dataPoint['AchievementsObtained'] . "</a> - " . $dataPoint['SoftcoreCount'] . " (" . $dataPoint['HardcoreCount'] . ")</td>";
                }
                echo "<td>" . $dataPoint['Points'];
                echo " <span class='TrueRatio'>(" . $dataPoint['RetroPoints'] . ")</span></td>";
                if ($dataPoint['Points'] == 0) {
                    echo "<td>0.00</td>";
                } else {
                    echo "<td>" . $dataPoint['RetroRatio'] . "</td>";
                }
                echo "<td>" . $dataPoint['TotalAwards'] . " - " . $dataPoint['CompletedAwards'] . " (" . $dataPoint['MasteredAwards'] . ")</td></tr>";
                $rank++;
                $userCount++;
            } else {
                if ($dataPoint['User'] == $user) {
                    $userRank = $rank;
                }
                $rank++;
            }
        }

        // Display the user if they are not in the list
        if ($user !== null) {
            if (!$userListed) {
                $userData = null;

                // Get and display the information for the logged in user if applicable
                $userData = getGlobalRankingData($type, $sort, $date, $user, null, $untracked, 0, 1);
                if (count($userData) > 0) {
                    // Add dummy row to seperate the user from the rest of the table
                    echo "<tr><td colspan='7'></td></tr>";
                    echo "<tr style='outline: thin solid'>";

                    // Get the user rank when sorting by points or retro points
                    if ($friends == 1) {
                        echo "<td>" . $userRank . "</td>";
                    } elseif ($type != 2) {
                        // Dont show rank on pages you are not actually ranked in. This would require rerunning the query just to find yourself
                        echo "<td></td>";
                    } else {
                        if ($sort < 10 && ($sort % 10) != 1) {
                            if ($sort == 5) {
                                echo "<td>" . getUserRank($user, 0) . "</td>";
                            } elseif ($sort == 6) {
                                echo "<td>" . getUserRank($user, 1) . "</td>";
                            } else {
                                echo "<td></td>";
                            }
                        }
                    }
                    echo "<td>";
                    echo GetUserAndTooltipDiv($userData[0]['User'], true);
                    echo GetUserAndTooltipDiv($userData[0]['User'], false);
                    echo "</td>";

                    // If viewing the daily leaderboard then link the total achievements obtained to the users history page for the day
                    if ($type == 0) {
                        echo "<td><a href='historyexamine.php?d=$dateUnix&u=" . $userData[0]['User'] . "'>" . $userData[0]['AchievementsObtained'] . "</a> - " . $userData[0]['SoftcoreCount'] . " (" . $userData[0]['HardcoreCount'] . ")</td>";
                    } else {
                        echo "<td>" . $userData[0]['AchievementsObtained'] . "</a> - " . $userData[0]['SoftcoreCount'] . " (" . $userData[0]['HardcoreCount'] . ")</td>";
                    }
                    echo "<td>" . $userData[0]['Points'];
                    echo " <span class='TrueRatio'>(" . $userData[0]['RetroPoints'] . ")</span></td>";
                    if ($userData[0]['Points'] == 0) {
                        echo "<td>0.00</td>";
                    } else {
                        echo "<td>" . $userData[0]['RetroRatio'] . "</td>";
                    }
                    echo "<td>" . $userData[0]['TotalAwards'] . " - " . $userData[0]['CompletedAwards'] . " (" . $userData[0]['MasteredAwards'] . ")</td></tr>";
                }
            }
        }
        echo "</tbody></table>";

        // Add page traversal
        echo "<div class='rightalign row'>";
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
    </div>
</div>
<?php RenderFooter(); ?>
</body>
<?php RenderHtmlEnd(); ?>

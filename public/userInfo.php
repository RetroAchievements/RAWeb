<?php

use App\Community\Enums\ArticleType;
use App\Community\Enums\ClaimFilters;
use App\Community\Enums\ClaimSorting;
use App\Community\Enums\ClaimSpecial;
use App\Community\Enums\ClaimType;
use App\Community\Enums\Rank;
use App\Community\Enums\RankType;
use App\Community\Enums\UserAction;
use App\Community\Enums\UserRelationship;
use App\Site\Enums\Permissions;
use App\Site\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Blade;

$userPage = request('user');
if (empty($userPage) || !isValidUsername($userPage)) {
    abort(404);
}

authenticateFromCookie($user, $permissions, $userDetails);

$maxNumGamesToFetch = requestInputSanitized('g', 5, 'integer');

if ($maxNumGamesToFetch < 1 || $maxNumGamesToFetch > 100) {
    abort(400);
}

$userPageModel = User::firstWhere('User', $userPage);
if (!$userPageModel) {
    abort(404);
}

$userMassData = getUserPageInfo($userPage, numGames: $maxNumGamesToFetch);
if (empty($userMassData)) {
    abort(404);
}

if ((int) $userMassData['Permissions'] < Permissions::Unregistered && $permissions < Permissions::Moderator) {
    abort(404);
}

$userPage = $userMassData['User'];
$userMotto = $userMassData['Motto'];
$userPageID = $userMassData['ID'];
$userSetRequestInformation = getUserRequestsInformation($userPageModel);
$userWallActive = $userMassData['UserWallActive'];
$userIsUntracked = $userMassData['Untracked'];

// Get wall
$numArticleComments = getRecentArticleComments(ArticleType::User, $userPageID, $commentData);

// Get user's feed
// $numFeedItems = getFeed( $userPage, 20, 0, $feedData, 0, 'individual' );

// Calc avg pcts:
$totalPctWon = 0.0;
$numGamesFound = 0;

// Achievement totals
$totalHardcoreAchievements = 0;
$totalSoftcoreAchievements = 0;

// Get user's list of played games and pct completion
$userCompletedGamesList = getUsersCompletedGamesAndMax($userPage);

$excludedConsoles = ["Hubs", "Events"];

foreach ($userCompletedGamesList as $nextGame) {
    if ($nextGame['PctWon'] > 0) {
        if (!in_array($nextGame['ConsoleName'], $excludedConsoles)) {
            $totalPctWon += $nextGame['PctWon'];
            $numGamesFound++;
            $totalHardcoreAchievements += $nextGame['NumAwardedHC'];
            $totalSoftcoreAchievements += ($nextGame['NumAwarded'] - $nextGame['NumAwardedHC']);
        }
    }
}

$avgPctWon = "0.0";
if ($numGamesFound > 0) {
    $avgPctWon = sprintf("%01.2f", ($totalPctWon / $numGamesFound) * 100.0);
}

sanitize_outputs(
    $userMotto,
    $userPage,
    $userMassData['RichPresenceMsg']
);

$pageTitle = "$userPage";

$daysRecentProgressToShow = 14; // fortnight

$userAwards = getUsersSiteAwards($userPage);

$userScoreData = getAwardedList(
    $userPageModel,
    0,
    $daysRecentProgressToShow,
    date("Y-m-d H:i:s", time() - 60 * 60 * 24 * $daysRecentProgressToShow),
    date("Y-m-d H:i:s", time())
);

// Get claim data if the user has jr dev or above permissions
if (getActiveClaimCount($userPage, true, true) > 0) {
    // Active claims sorted by game title
    $userClaimData = getFilteredClaims(
        claimFilter: ClaimFilters::AllActiveClaims,
        sortType: ClaimSorting::GameAscending,
        username: $userPage
    );
}

RenderOpenGraphMetadata(
    $userPage,
    "user",
    media_asset('/UserPic/' . $userPage . '.png'),
    "$userPage Profile"
);
RenderContentStart($userPage);
?>
<script defer src="https://www.gstatic.com/charts/loader.js"></script>
<script>
  document.addEventListener('DOMContentLoaded', function() {
    if (typeof google !== 'undefined') {
        google.load('visualization', '1.0', { 'packages': ['corechart'] });
        google.setOnLoadCallback(drawCharts);
    }
  });

  function drawCharts() {
      var dataRecentProgress = new google.visualization.DataTable();

      // Declare columns
      dataRecentProgress.addColumn('date', 'Date');    // NOT date! this is non-continuous data
      dataRecentProgress.addColumn('number', 'Hardcore Score');
      dataRecentProgress.addColumn('number', 'Softcore Score');

      dataRecentProgress.addRows([
          <?php
          $count = 0;
          foreach ($userScoreData as $dayInfo) {
              if ($count++ > 0) {
                  echo ", ";
              }

              $nextDate = Carbon::parse($dayInfo['Date']);
              $nextYear = $nextDate->year;
              $nextMonth = $nextDate->month;
              $nextDay = $nextDate->day;
              $dateStr = $nextDate->format('d M Y');

              $hardcoreValue = $dayInfo['CumulHardcoreScore'];
              $softcoreValue = $dayInfo['CumulSoftcoreScore'];

              echo "[ {v:new Date($nextYear,$nextMonth,$nextDay), f:'$dateStr'}, $hardcoreValue, $softcoreValue ]";
          }
          ?>
      ]);

      var optionsRecentProcess = {
          backgroundColor: 'transparent',
          title: 'Recent Progress',
          titleTextStyle: { color: '#186DEE' },
          hAxis: {
              textStyle: { color: '#186DEE' },
              slantedTextAngle: 90
          },
          vAxis: { textStyle: { color: '#186DEE' } },
          legend: { position: 'none' },
          chartArea: {
              left: 42,
              width: 458,
              'height': '100%'
          },
          showRowNumber: false,
          view: { columns: [0, 1] },
          colors: ['#186DEE', '#8c8c8c'],
      };

      function resize() {
          chartRecentProgress = new google.visualization.AreaChart(document.getElementById('chart_recentprogress'));
          chartRecentProgress.draw(dataRecentProgress, optionsRecentProcess);
      }

      window.onload = resize();
      window.onresize = resize;
  }
</script>

<article class="overflow-y-hidden">
    <?php
    echo "<div class='navpath'>";
    echo "<a href='/userList.php'>All Users</a>";
    echo " &raquo; <b>$userPage</b>";
    echo "</div>";

    echo "<div class='usersummary'>";
    echo "<h3>$userPage</h3>";
    echo "<img src='" . media_asset("/UserPic/$userPage.png") . "' alt='$userPage' align='right' width='128' height='128' class='rounded-sm'>";

    if (isset($userMotto)) {
        echo "<div class='mottocontainer'>";
        echo "<span class='usermotto'>$userMotto</span>";
        echo "</div>";
    }

    if (isset($user) && ($user !== $userPage)) {
        echo "<div class='flex flex-col sm:flex-row justify-center sm:justify-start sm:gap-1'>";

        $myFriendshipType = GetFriendship($user, $userPage);
        $areTheyFollowingMe = GetFriendship($userPage, $user) == UserRelationship::Following;

        echo "<div class='flex'>";
        switch ($myFriendshipType) {
            case UserRelationship::Following:
                echo "<form class='inline-block' action='/request/user/update-relationship.php' method='post'>";
                echo csrf_field();
                echo "<input type='hidden' name='user' value='$userPage'>";
                echo "<input type='hidden' name='action' value='" . UserRelationship::NotFollowing . "'>";
                echo "<button class='btn btn-link !pl-0'>Unfollow</button>";
                echo "</form>";
                break;
            case UserRelationship::NotFollowing:
                echo "<form class='inline-block' action='/request/user/update-relationship.php' method='post'>";
                echo csrf_field();
                echo "<input type='hidden' name='user' value='$userPage'>";
                echo "<input type='hidden' name='action' value='" . UserRelationship::Following . "'>";
                echo "<button class='btn btn-link !pl-0'>Follow" . ($areTheyFollowingMe ? ' Back' : '') . "</button>";
                echo "</form>";
                break;
        }

        if ($myFriendshipType != UserRelationship::Blocked) {
            echo "<form class='inline-block' action='/request/user/update-relationship.php' method='post'>";
            echo csrf_field();
            echo "<input type='hidden' name='user' value='$userPage'>";
            echo "<input type='hidden' name='action' value='" . UserRelationship::Blocked . "'>";
            echo "<button class='btn btn-link'>Block</button>";
            echo "</form>";
        } else {
            echo "<form class='inline-block' action='/request/user/update-relationship.php' method='post'>";
            echo csrf_field();
            echo "<input type='hidden' name='user' value='$userPage'>";
            echo "<input type='hidden' name='action' value='" . UserRelationship::NotFollowing . "'>";
            echo "<button class='btn btn-link'>Unblock</button>";
            echo "</form>";
        }
        echo "<a class='btn btn-link' href='" . route('message.create') . "?to=$userPage'>Message</a>";
        echo "</div>";

        if ($areTheyFollowingMe) {
            echo "<p class='sm:px-3'>Follows you</p>";
        }

        echo "</div>";
    }

    echo "<br>";

    $niceDateJoined = $userMassData['MemberSince'] ? getNiceDate(strtotime($userMassData['MemberSince'])) : null;
    if ($niceDateJoined) {
        echo "Member Since: $niceDateJoined<br>";
    }
    // LastLogin is updated on any activity -> "LastActivity"
    $niceDateLogin = $userMassData['LastActivity'] ? getNiceDate(strtotime($userMassData['LastActivity'])) : null;
    if ($niceDateLogin) {
        echo "Last Activity: $niceDateLogin<br>";
    }
    echo "Account Type: <b>[" . Permissions::toString($userMassData['Permissions']) . "]</b><br>";
    echo "<br>";

    $totalHardcorePoints = $userMassData['TotalPoints'];
    if ($totalHardcorePoints > 0) {
        $totalTruePoints = $userMassData['TotalTruePoints'];

        $retRatio = sprintf("%01.2f", $totalTruePoints / $totalHardcorePoints);
        echo "Hardcore Points: " . localized_number($totalHardcorePoints) . "<span class='TrueRatio'> (" . localized_number($totalTruePoints) . ")</span><br>";
        if ($user) {
            echo "Hardcore Achievements: " . localized_number($totalHardcoreAchievements) . "<br>";
        }

        echo "Site Rank: ";
        if ($userIsUntracked) {
            echo "<b>Untracked</b>";
        } elseif ($totalHardcorePoints < Rank::MIN_POINTS) {
            echo "<i>Needs at least " . Rank::MIN_POINTS . " points.</i>";
        } else {
            $countRankedUsers = countRankedUsers();
            $userRank = $userMassData['Rank'];
            $rankPct = sprintf("%1.2f", ($userRank / $countRankedUsers) * 100.0);
            $rankPctLabel = $userRank > 100 ? "(Top $rankPct%)" : "";
            $rankOffset = (int) (($userRank - 1) / 25) * 25;
            echo "<a href='/globalRanking.php?s=5&t=2&o=$rankOffset'>#" . localized_number($userRank) . "</a> / " . localized_number($countRankedUsers) . " ranked users $rankPctLabel";
        }
        echo "<br>";

        echo "Retro Ratio: <span class='TrueRatio'><b>$retRatio</b></span><br>";
        echo "<br>";
    }

    $totalSoftcorePoints = $userMassData['TotalSoftcorePoints'];
    if ($totalSoftcorePoints > 0) {
        echo "Softcore Points: " . localized_number($totalSoftcorePoints) . "<br>";
        echo "Softcore Achievements: " . localized_number($totalSoftcoreAchievements) . "<br>";

        echo "Softcore Rank: ";
        if ($userIsUntracked) {
            echo "<b>Untracked</b>";
        } elseif ($totalSoftcorePoints < Rank::MIN_POINTS) {
            echo "<i>Needs at least " . Rank::MIN_POINTS . " points.</i>";
        } else {
            $countRankedUsers = countRankedUsers(RankType::Softcore);
            $userRankSoftcore = getUserRank($userPage, RankType::Softcore);
            $rankPct = sprintf("%1.2f", ($userRankSoftcore / $countRankedUsers) * 100.0);
            $rankPctLabel = $userRankSoftcore > 100 ? "(Top $rankPct%)" : "";
            $rankOffset = (int) (($userRankSoftcore - 1) / 25) * 25;
            echo "<a href='/globalRanking.php?s=2&t=2&o=$rankOffset'>#" . localized_number($userRankSoftcore) . "</a> / " . localized_number($countRankedUsers) . " ranked users $rankPctLabel";
        }
        echo "<br>";
        echo "<br>";
    }

    if ($user) {
        echo "Average Completion: <b>$avgPctWon%</b><br><br>";
    }

    echo "<a href='/forumposthistory.php?u=$userPage'>Forum Post History</a>";
    echo "<br>";

    echo "<a href='/setRequestList.php?u=$userPage'>Requested Sets</a>"
        . " - " . $userSetRequestInformation['used']
        . " of " . $userSetRequestInformation['total'] . " Requests Made";
    echo "<br><br>";

    if (!empty($userMassData['RichPresenceMsg']) && $userMassData['RichPresenceMsg'] !== 'Unknown') {
        echo "<div class='mottocontainer'>Last seen ";
        if (!empty($userMassData['LastGame'])) {
            echo ' in ' . gameAvatar($userMassData['LastGame'], iconSize: 22) . '<br>';
        }
        echo "<code>" . $userMassData['RichPresenceMsg'] . "</code></div>";
    }

    $contribCount = $userMassData['ContribCount'];
    $contribYield = $userMassData['ContribYield'];
    if ($contribCount > 0) {
        echo "<strong>$userPage Developer Information:</strong><br>";
        echo "<a href='" . route('developer.sets', $userPageModel) . "'>View all achievements sets <b>$userPage</b> has worked on.</a><br>";
        echo "<a href='/individualdevstats.php?u=$userPage'>View detailed stats for <b>$userPage</b>.</a><br>";
        echo "<a href='/claimlist.php?u=$userPage'>View claims for <b>$userPage</b>.</a></br>";
        if (isset($user) && $permissions >= Permissions::Registered) {
            $openTicketsData = countOpenTicketsByDev($userPage);
            echo "<a href='/ticketmanager.php?u=$userPage'>Open Tickets: <b>" . array_sum($openTicketsData) . "</b></a><br>";
        }
        echo "Achievements won by others: <b>" . localized_number($contribCount) . "</b><br>";
        echo "Points awarded to others: <b>" . localized_number($contribYield) . "</b><br><br>";
    }

    // Display the users active claims
    if (isset($userClaimData) && (is_countable($userClaimData) ? count($userClaimData) : 0) > 0) {
        echo "<b>$userPage's</b> current claims:</br>";
        foreach ($userClaimData as $claim) {
            $details = "";
            $isCollab = $claim['ClaimType'] == ClaimType::Collaboration;
            $isSpecial = $claim['Special'] != ClaimSpecial::None;
            if ($isCollab) {
                $details = " (" . ClaimType::toString(ClaimType::Collaboration) . ")";
            } else {
                if (!$isSpecial) {
                    $details = "*";
                }
            }
            $claimGameData = [
                'ID' => $claim['GameID'],
                'Title' => $claim['GameTitle'],
                'ImageIcon' => $claim['GameIcon'],
                'ConsoleName' => $claim['ConsoleName'],
            ];
            echo gameAvatar($claim, iconSize: 22);
            echo $details . '<br>';
        }
        echo "* Counts against reservation limit</br></br>";
    }

    echo "</div>"; // usersummary

    if (isset($user) && $permissions >= Permissions::Moderator) {
        echo "<div class='devbox'>";
        echo "<span onclick=\"$('#devboxcontent').toggle(); return false;\">Admin ▼</span>";
        echo "<div id='devboxcontent' style='display: none'>";

        echo "<table>";

        if ($permissions >= $userMassData['Permissions'] && ($user != $userPage)) {
            echo "<tr>";
            echo "<form method='post' action='/request/user/update.php'>";
            echo csrf_field();
            echo "<input type='hidden' name='property' value='" . UserAction::UpdatePermissions . "' />";
            echo "<input type='hidden' name='target' value='$userPage' />";
            echo "<td class='text-right'>";
            echo "<button class='btn'>Update Account Type</button>";
            echo "</td><td>";
            echo "<select name='value' >";
            $i = Permissions::Banned;
            // Don't do this, looks weird when trying to change someone above you
            // while( $i <= $permissions && ( $i <= Permissions::Developer || $user == 'Scott' ) )
            while ($i <= $permissions) {
                if ($userMassData['Permissions'] == $i) {
                    echo "<option value='$i' selected >($i): " . Permissions::toString($i) . " (current)</option>";
                } else {
                    echo "<option value='$i'>($i): " . Permissions::toString($i) . "</option>";
                }
                $i++;
            }
            echo "</select>";

            echo "</td></form></tr>";
        }

        echo "<tr><td class='text-right'>";
        echo "<form method='post' action='/request/user/update.php'>";
        echo csrf_field();
        echo "<input type='hidden' name='property' value='" . UserAction::PatreonBadge . "' />";
        echo "<input type='hidden' name='target' value='$userPage' />";
        echo "<input type='hidden' name='value' value='0' />";
        echo "<button class='btn'>Toggle Patreon Supporter</button>";
        echo "</form>";
        echo "</td><td>";
        echo HasPatreonBadge($userPage) ? "Patreon Supporter" : "Not a Patreon Supporter";
        echo "</td></tr>";

        echo "<tr><td class='text-right'>";
        echo "<form method='post' action='/request/user/update.php'>";
        echo csrf_field();
        echo "<input type='hidden' name='property' value='" . UserAction::LegendBadge . "' />";
        echo "<input type='hidden' name='target' value='$userPage' />";
        echo "<input type='hidden' name='value' value='0' />";
        echo "<button class='btn'>Toggle Certified Legend</button>";
        echo "</form>";
        echo "</td><td>";
        echo HasCertifiedLegendBadge($userPage) ? "Certified Legend" : "Not Yet Legendary";
        echo "</td></tr>";

        $newValue = $userIsUntracked ? 0 : 1;
        echo "<tr><td class='text-right'>";
        echo "<form method='post' action='/request/user/update.php'>";
        echo csrf_field();
        echo "<input type='hidden' name='property' value='" . UserAction::TrackedStatus . "' />";
        echo "<input type='hidden' name='target' value='$userPage' />";
        echo "<input type='hidden' name='value' value='$newValue' />";
        echo "<button class='btn btn-danger'>Toggle Tracked Status</button>";
        echo "</form>";
        echo "</td><td style='width: 100%'>";
        echo ($userIsUntracked == 1) ? "Untracked User" : "Tracked User";
        echo "</td></tr>";

        echo "<tr><td class='text-right'>";
        echo "<form method='post' action='/request/user/remove-avatar.php' onsubmit='return confirm(\"Are you sure you want to permanently delete this avatar?\")'>";
        echo csrf_field();
        echo "<input type='hidden' name='user' value='$userPage' />";
        echo "<button class='btn btn-danger'>Remove Avatar</button>";
        echo "</form>";
        echo "</td></tr>";

        echo "<tr><td colspan=2>";
        echo "<div class='commentscomponent left'>";
        $numLogs = getRecentArticleComments(ArticleType::UserModeration, $userPageID, $logs);
        RenderCommentsComponent($user,
            $numLogs,
            $logs,
            $userPageID,
            ArticleType::UserModeration,
            $permissions
        );
        echo "</div>";
        echo "</td></tr>";

        echo "</table>";

        echo "</div>"; // devboxcontent

        echo "</div>"; // devbox
    }

    $canShowProgressionStatusComponent =
        !empty($userCompletedGamesList)
        // Needs at least one non-event game.
        && count(array_filter($userCompletedGamesList, fn ($game) => $game['ConsoleID'] != 101)) > 0;

    if ($canShowProgressionStatusComponent) {
        echo "<div class='my-8'>";
        echo Blade::render('
            <x-user-progression-status
                :userCompletionProgress="$userCompletionProgress"
                :userSiteAwards="$userSiteAwards"
                :userRecentlyPlayed="$userRecentlyPlayed"
                :userHardcorePoints="$userHardcorePoints"
                :userSoftcorePoints="$userSoftcorePoints"
            />
        ', [
            'userCompletionProgress' => $userCompletedGamesList,
            'userSiteAwards' => $userAwards,
            'userRecentlyPlayed' => $userMassData['RecentlyPlayed'],
            'userHardcorePoints' => $totalHardcorePoints,
            'userSoftcorePoints' => $totalSoftcorePoints,
        ]);
        echo "</div>";
    }

    echo "<div class='my-8'>";
    echo Blade::render('
        <x-user-recently-played
            :recentlyPlayedCount="$recentlyPlayedCount"
            :recentlyPlayedEntities="$recentlyPlayedEntities"
            :recentAchievementEntities="$recentAchievementEntities"
            :recentAwardedEntities="$recentAwardedEntities"
            :targetUsername="$targetUsername"
            :userAwards="$userAwards"
        />
    ', [
        'recentlyPlayedCount' => $userMassData['RecentlyPlayedCount'] ?? 0,
        'recentlyPlayedEntities' => $userMassData['RecentlyPlayed'] ?? [],
        'recentAchievementEntities' => $userMassData['RecentAchievements'] ?? [],
        'recentAwardedEntities' => $userMassData['Awarded'] ?? [],
        'targetUsername' => $user ?? '',
        'userAwards' => $userAwards,
    ]);

    $recentlyPlayedCount = $userMassData['RecentlyPlayedCount'];
    if ($maxNumGamesToFetch == 5 && $recentlyPlayedCount == 5) {
        echo "<div class='text-right'><a class='btn btn-link' href='/user/$userPage?g=15'>more...</a></div>";
    }
    echo "</div>";

    echo "<div class='commentscomponent left mt-8'>";

    echo "<h4>User Wall</h4>";

    if ($userWallActive) {
        // passing 'null' for $user disables the ability to add comments
        RenderCommentsComponent(
            !isUserBlocking($userPage, $user) ? $user : null,
            $numArticleComments,
            $commentData,
            $userPageID,
            ArticleType::User,
            $permissions
        );
    } else {
        echo "<div>";
        echo "<i>This user has disabled comments.</i>";
        echo "</div>";
    }

    echo "</div>";
    ?>
</article>
<?php view()->share('sidebar', true) ?>
<aside>
    <?php
    $prefersHiddenUserCompletedSets = request()->cookie('prefers_hidden_user_completed_sets') === 'true';

    RenderSiteAwards($userAwards, $userPage);

    if (count($userCompletedGamesList) >= 1) {
        RenderCompletedGamesList($userCompletedGamesList, $userPage, $prefersHiddenUserCompletedSets);
    }

    echo "<div id='achdistribution' class='component'>";
    echo "<h3>Recent Progress</h3>";
    echo "<div id='chart_recentprogress' class='mb-5 min-h-[200px]'></div>";
    echo "<div class='text-right'><a class='btn btn-link' href='/history.php?u=$userPage'>more...</a></div>";
    echo "</div>";

    if ($user !== null && $user === $userPage) {
        $friendCount = getFriendCount($user);
        echo Blade::render('<x-user.followed-leaderboard-cta :friendCount="$friendCount" />', ['friendCount' => $friendCount]);
    }
    ?>
</aside>
<?php RenderContentEnd(); ?>

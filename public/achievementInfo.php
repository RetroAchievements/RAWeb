<?php

use RA\AchievementPoints;
use RA\AchievementType;
use RA\ArticleType;
use RA\Permissions;
use RA\Shortcode;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../lib/bootstrap.php';

authenticateFromCookie($user, $permissions, $userDetails);

$achievementID = requestInputSanitized('ID', 0, 'integer');

$dataOut = null;
if ($achievementID == 0 || !getAchievementMetadata($achievementID, $dataOut)) {
    header("Location: " . getenv('APP_URL') . "?e=unknownachievement");
    exit;
}

$achievementTitle = $dataOut['AchievementTitle'];
$desc = $dataOut['Description'];
$achFlags = (int) $dataOut['Flags'];
$achPoints = (int) $dataOut['Points'];
$achTruePoints = (int) $dataOut['TrueRatio'];
$gameTitle = $dataOut['GameTitle'];
$badgeName = $dataOut['BadgeName'];
$consoleID = $dataOut['ConsoleID'];
$consoleName = $dataOut['ConsoleName'];
$gameID = $dataOut['GameID'];
$embedVidURL = $dataOut['AssocVideo'];
$author = $dataOut['Author'];
$dateCreated = $dataOut['DateCreated'];
$dateModified = $dataOut['DateModified'];
$achMem = $dataOut['MemAddr'];
$isSoleAuthor = false;

sanitize_outputs(
    $achievementTitle,
    $desc,
    $gameTitle,
    $consoleName,
    $author
);

$numLeaderboards = getLeaderboardsForGame($gameID, $lbData, $user);

$numWinners = 0;
$numPossibleWinners = 0;
$numRecentWinners = 0;

getAchievementUnlocksData($achievementID, $numWinners, $numPossibleWinners, $numRecentWinners, $winnerInfo, $user, 0, 50);

// Determine if the logged in user is the sole author of the set
if ($permissions >= Permissions::JuniorDeveloper && isset($user)) {
    $isSoleAuthor = checkIfSoleDeveloper($user, $gameID);
}

$dateWonLocal = "";
foreach ($winnerInfo as $userObject) {
    if ($userObject['User'] == $user) {
        $dateWonLocal = $userObject['DateAwarded'];
        break;
    }
}

if ($dateWonLocal === "") {
    $hasAward = playerHasUnlock($user, $achievementID);
    if ($hasAward['HasHardcore']) {
        $dateWonLocal = $hasAward['HardcoreDate'];
    } elseif ($hasAward['HasRegular']) {
        $dateWonLocal = $hasAward['RegularDate'];
    }
}

$achievedLocal = ($dateWonLocal !== "");

$numArticleComments = getArticleComments(ArticleType::Achievement, $achievementID, 0, 20, $commentData);

getCodeNotes($gameID, $codeNotes);

$errorCode = requestInputSanitized('e');

RenderHtmlStart(true);
?>
<head prefix="og: http://ogp.me/ns# retroachievements: http://ogp.me/ns/apps/retroachievements#">
    <?php RenderSharedHeader(); ?>
    <?php RenderOpenGraphMetadata("$achievementTitle in $gameTitle ($consoleName)", "achievement", "/Badge/$badgeName" . ".png", "/achievement/$achievementID", "$gameTitle ($consoleName) - $desc"); ?>
    <?php RenderTitleTag($achievementTitle); ?>
</head>

<body>
<?php RenderHeader($userDetails); ?>
<?php if ($permissions >= Permissions::Developer || ($permissions >= Permissions::JuniorDeveloper && $isSoleAuthor && $achFlags === AchievementType::Unofficial)): ?>
    <script>
    function updateAchievementDetails() {
        showStatusMessage('Updating...');
        $.ajax({
            type: "POST",
            url: '/request/achievement/update-base.php',
            dataType: "json",
            data: {
                'a': <?= $achievementID ?>,
                't': $('#titleinput').val(),
                'd': $('#descriptioninput').val(),
                'p': $('#pointsinput').val(),
            },
            error: function (xhr, status, error) {
                showStatusFailure('Error: ' + (error || 'unknown error'));
            }
        })
            .done(function (data) {
                if (!data.success) {
                    showStatusFailure('Error: ' + (data.error || 'unknown error'));
                    return;
                }
                window.location = window.location.href.split("?")[0] + "?e=modify_achievement_ok";
            });
    }

    <?php if ($permissions >= Permissions::Developer): ?>
      function PostEmbedUpdate() {
        var url = $('#embedurlinput').val();
        url = replaceAll('http', '_http_', url);

        showStatusMessage('Updating...');

        if ( url == '') {
            showStatusFailure('Error: Blank url');
            return;
        }

        $.ajax({
          type: "POST",
          url: '/request/achievement/update.php',
          dataType: "json",
          data: {
            'u': '<?= $user ?>',
            'a': <?= $achievementID ?>,
            'f': 2,
            'v': url
          },
          error: function (xhr, status, error) {
            showStatusFailure('Error: ' + (error || 'unknown error'));
          }
        })
          .done(function (data) {
            if (!data.success) {
              showStatusFailure('Error: ' + (data.error || 'unknown error'));
              return;
            }
            window.location = window.location.href.split("?")[0] + "?e=changeok";
          });
      }

      function updateAchievementTypeFlag(typeFlag) {
        if (!confirm(`Are you sure you want to ${(typeFlag === <?= AchievementType::OfficialCore ?> ? 'promote' : 'demote')} these achievements?`)) {
          return;
        }

        showStatusMessage('Updating...');
        $.ajax({
          type: "POST",
          url: '/request/achievement/update.php',
          dataType: "json",
          data: {
            'a': <?= $achievementID ?>,
            'f': 3,
            'u': '<?= $user ?>',
            'v': typeFlag
          },
          error: function (xhr, status, error) {
            showStatusFailure('Error: ' + (error || 'unknown error'));
          }
        })
          .done(function (data) {
            if (!data.success) {
              showStatusFailure('Error: ' + (data.error || 'unknown error'));
              return;
            }
            window.location = window.location.href.split("?")[0] + "?e=changeok";
          });
      }
      <?php endif ?>
    </script>
<?php endif ?>

<div id="mainpage">
    <div id="leftcontainer">
        <?php
        RenderErrorCodeWarning($errorCode);
        echo "<div id='achievement'>";

        echo "<div class='navpath'>";
        echo "<a href='/gameList.php'>All Games</a>";
        echo " &raquo; <a href='/gameList.php?c=$consoleID'>$consoleName</a>";
        echo " &raquo; <a href='/game/$gameID'>$gameTitle</a>";
        echo " &raquo; <b>$achievementTitle</b>";
        echo "</div>"; // navpath

        echo "<h3 class='longheader'>$gameTitle ($consoleName)</h3>";

        $fileSuffix = ($user == "" || !$achievedLocal) ? '_lock' : '';
        $badgeFullPath = asset("Badge/$badgeName$fileSuffix.png");

        echo "<table class='nicebox'><tbody>";

        $descAttr = attributeEscape($desc);
        echo "<tr>";
        echo "<td style='width:70px'>";
        echo "<div id='achievemententryicon'>";
        echo "<a href=\"/achievement/$achievementID\"><img src=\"$badgeFullPath\" title=\"$gameTitle ($achPoints)\n$descAttr\" alt=\"$descAttr\" align=\"left\" width=\"64\" height=\"64\" /></a>";
        echo "</div>"; // achievemententryicon
        echo "</td>";

        echo "<td>";
        echo "<div id='achievemententry'>";

        if ($achievedLocal) {
            $niceDateWon = date("d M, Y H:i", strtotime($dateWonLocal));
            echo "<small style='float: right; text-align: right;' class='smalldate'>unlocked on<br>$niceDateWon</small>";
        }
        echo "<a href='/achievement/$achievementID'><strong>$achievementTitle</strong></a> ($achPoints)<span class='TrueRatio'> ($achTruePoints)</span><br>";
        echo "$desc<br>";

        echo "</div>"; // achievemententry
        echo "</td>";

        echo "</tr>";
        echo "</tbody></table>";

        if ($numPossibleWinners > 0) {
            $recentWinnersPct = sprintf("%01.0f", ($numWinners / $numPossibleWinners) * 100);
        } else {
            $recentWinnersPct = sprintf("%01.0f", 0);
        }

        $niceDateCreated = date("d M, Y H:i", strtotime($dateCreated));
        $niceDateModified = date("d M, Y H:i", strtotime($dateModified));

        echo "<p class='smalldata'>";
        echo "<small>";
        if ($achFlags === AchievementType::Unofficial) {
            echo "<b>Unofficial Achievement</b><br>";
        }
        echo "Created by " . GetUserAndTooltipDiv($author, false) . " on: $niceDateCreated<br>Last modified: $niceDateModified<br>";
        echo "</small>";
        echo "</p>";

        echo "Won by <b>$numWinners</b> of <b>$numPossibleWinners</b> possible players ($recentWinnersPct%)";

        if (isset($user) && $permissions >= Permissions::Registered) {
            echo "<br>";
            $countTickets = countOpenTicketsByAchievement($achievementID);
            if ($countTickets > 0) {
                echo "<small><a href='/ticketmanager.php?a=$achievementID'>This achievement has $countTickets open tickets</a></small><br>";
            }
            if (isAllowedToSubmitTickets($user)) {
                echo "<small><a href='/reportissue.php?i=$achievementID'>Report an issue for this achievement.</a></small>";
            }
        }
        echo "<br>";

        if ($achievedLocal) {
            echo "<div class='devbox'>";
            echo "<span onclick=\"$('#resetboxcontent').toggle(); return false;\">Reset Progress</span><br>";
            echo "<div id='resetboxcontent' style='display: none'>";
            echo "<form action='/request/user/reset-achievements.php' method='post' onsubmit='return confirm(\"Are you sure you want to reset this progress?\")'>";
            echo "<input type='hidden' name='a' value='$achievementID'>";
            echo "<input type='submit' value='Reset this achievement'>";
            echo "</form>";
            echo "</div></div>";
        }
        echo "<br>";

        if (isset($user) && $permissions >= Permissions::JuniorDeveloper) {
            echo "<div class='devbox mb-3'>";
            echo "<span onclick=\"$('#devboxcontent').toggle(); return false;\">Dev (Click to show):</span><br>";
            echo "<div id='devboxcontent' style='display: none'>";
            RenderStatusWidget();

            if ($permissions >= Permissions::Developer || ($isSoleAuthor && $permissions >= Permissions::JuniorDeveloper && $achFlags === AchievementType::Unofficial)) {
                echo "<div>Update achievement details:</div>";
                echo "<table><tbody>";
                echo "<tr><td>Title:</td><td style='width:100%'><input id='titleinput' type='text' name='t' value='" . attributeEscape($achievementTitle) . "' style='width:100%' maxlength='64'></td></tr>";
                echo "<tr><td>Description:</td><td><input id='descriptioninput' type='text' name='d' value='" . attributeEscape($desc) . "' style='width:100%' maxlength='255'></td></tr>";
                echo "<tr><td>Points:</td><td>";
                echo "<select id='pointsinput' name='p'>";
                foreach (AchievementPoints::VALID as $pointsOption) {
                    echo "<option value='$pointsOption' " . ($achPoints === $pointsOption ? 'selected' : '') . ">$pointsOption</option>";
                }
                echo "</select>";
                echo "</td></tr>";
                echo "</tbody></table>";
                echo "&nbsp;<input type='submit' style='float: right;' value='Update' onclick=\"updateAchievementDetails()\" /><br><br>";

                echo "<form class='mb-2' method='post' action='/request/achievement/update-image.php' enctype='multipart/form-data'>";
                echo "<label>Badge<br>";
                echo "<input type='hidden' name='i' value='$achievementID'>";
                echo "<input type='file' accept='.png,.jpg,.gif' name='file'>";
                echo "</label>";
                echo "<input type='submit' name='submit' style='float: right' value='Submit'>";
                echo "</form><br>";
            }

            if ($permissions >= Permissions::Developer) {
                echo "<div class='devbox'>";
                echo "<li><span onclick=\"$('#embedcontent').toggle(); return false;\">Set embedded video URL: (Click to show accepted formats):</span><br></li>";
                echo "<div id='embedcontent' style='display: none'>";
                echo "<div style='clear:both;'></div>"; ?>
                Examples for accepted formats:<br>
                <p style="margin-bottom: 20px; float: left; clear: both;">
                    <small style="width:50%; word-break: break-word; float: left">
                        https://www.youtube.com/v/ID<br>
                        https://www.youtube.com/watch?v=ID<br>
                        https://youtu.be/ID<br>
                        https://www.youtube.com/embed/ID<br>
                        https://www.youtube.com/watch?v=ID<br>
                        www.youtube.com/watch?v=ID<br>
                        https://www.twitch.tv/videos/ID<br>
                        https://www.twitch.tv/collections/ID<br>
                        https://www.twitch.tv/ID/v/ID<br>
                        https://clips.twitch.tv/ID<br>
                    </small>
                    <small style="width:50%; word-break: break-word; float: left">
                        https://imgur.com/gallery/ID -> turns out as link without extension<br>
                        https://imgur.com/a/ID.gif -> will use .gifv instead<br>
                        https://imgur.com/gallery/ID.gifv<br>
                        https://imgur.com/a/ID.gifv<br>
                        https://i.imgur.com/ID.gifv<br>
                        https://i.imgur.com/ID.webm<br>
                        https://i.imgur.com/ID.mp4<br>
                    </small>
                </p>
                <?php
                echo "<div style='clear:both;'></div>";
                echo "</div>"; // embed devbox
                echo "</div>"; // embed devbox
                echo "<table><tbody>";
                echo "<input type='hidden' name='a' value='$achievementID' />";
                echo "<input type='hidden' name='f' value='2' />";
                echo "<tr><td>Embed:</td><td style='width:100%'><input id='embedurlinput' type='text' name='v' value='$embedVidURL' style='width:100%;'/></td></tr>";
                echo "</tbody></table>";
                echo "&nbsp;<input type='submit' style='float: right;' value='Submit' onclick=\"PostEmbedUpdate()\" /><br><br>";

                if ($achFlags === AchievementType::OfficialCore) {
                    echo "<li>State: Official&nbsp;<button type='button' onclick='updateAchievementTypeFlag(" . AchievementType::Unofficial . ")'>Demote To Unofficial</button></li>";
                }
                if ($achFlags === AchievementType::Unofficial) {
                    echo "<li>State: Unofficial&nbsp;<button type='button' onclick='updateAchievementTypeFlag(" . AchievementType::OfficialCore . ")'>Promote To Official</button></li>";
                }
            }

            echo "<li> Achievement ID: " . $achievementID . "</li>";

            echo "<div>";
            echo "<li>Mem:</li>";
            echo "<code>" . htmlspecialchars($achMem) . "</code>";
            echo "<li>Mem explained:</li>";
            echo "<code>" . getAchievementPatchReadableHTML($achMem, $codeNotes) . "</code>";
            echo "</div>";

            echo "</div>"; // devboxcontent
            echo "</div>"; // devbox
        }

        if (!empty($embedVidURL)) {
            echo Shortcode::render($embedVidURL, ['imgur' => true]);
        }

        RenderCommentsComponent(
            $user,
            $numArticleComments,
            $commentData,
            $achievementID,
            ArticleType::Achievement,
            $permissions
        );

        echo "</div>"; // achievement

        /**
         * id attribute used for scraping. NOTE: this will be deprecated. Use API_GetAchievementUnlocks instead
         */
        echo "<div id='recentwinners'>";
        echo "<h3>Recent Winners</h3>";
        if (empty($winnerInfo)) {
            echo "Nobody yet! Will you be the first?!<br>";
        } else {
            echo "<table><tbody>";
            echo "<tr><th colspan='2'>User</th><th>Hardcore?</th><th>Earned On</th></tr>";
            $iter = 0;
            foreach ($winnerInfo as $userObject) {
                $userWinner = $userObject['User'];
                if ($userWinner == null || $userObject['DateAwarded'] == null) {
                    continue;
                }

                $niceDateWon = date("d M, Y H:i", strtotime($userObject['DateAwarded']));

                echo "<tr>";

                echo "<td style='width:34px'>";
                echo GetUserAndTooltipDiv($userWinner, true);
                echo "</td>";
                echo "<td>";
                echo GetUserAndTooltipDiv($userWinner, false);
                echo "</td>";
                echo "<td>";
                if ($userObject['HardcoreMode']) {
                    echo "Hardcore!";
                }
                echo "</td>";

                echo "<td>";
                echo "<small>$niceDateWon</small>";
                echo "</td>";

                echo "</tr>";
            }

            echo "</tbody></table>";
        }
        echo "</div>";
        ?>
    </div>
    <div id="rightcontainer">
        <?php
        if ($user !== null) {
            RenderScoreLeaderboardComponent($user, true);
        }
        RenderGameLeaderboardsComponent($lbData);
        ?>
    </div>
</div>
<?php RenderFooter(); ?>
</body>
<?php RenderHtmlEnd(); ?>

<?php

use App\Support\Shortcode\Shortcode;
use RA\AchievementPoints;
use RA\AchievementType;
use RA\ArticleType;
use RA\LinkStyle;
use RA\Permissions;

authenticateFromCookie($user, $permissions, $userDetails);

$achievementID = (int) request('achievement');
if (empty($achievementID)) {
    abort(404);
}

$dataOut = null;
getAchievementMetadata($achievementID, $dataOut);
if (empty($dataOut)) {
    abort(404);
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

$achievementTitleRaw = $dataOut['AchievementTitle'];
$achievementDescriptionRaw = $dataOut['Description'];
$gameTitleRaw = $dataOut['GameTitle'];

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

RenderOpenGraphMetadata("$achievementTitleRaw in $gameTitleRaw ($consoleName)", "achievement", media_asset("/Badge/$badgeName" . ".png"), "$gameTitleRaw ($consoleName) - $achievementDescriptionRaw");
RenderContentStart($achievementTitleRaw);
?>
<?php if ($permissions >= Permissions::Developer || ($permissions >= Permissions::JuniorDeveloper && $isSoleAuthor && $achFlags === AchievementType::Unofficial)): ?>
    <script>
    function updateAchievementDetails() {
        showStatusMessage('Updating...');

        var $title = $('#titleinput');
        var $description = $('#descriptioninput');
        if (new Blob([$title.val()]).size > $title.attr('maxlength')) {
            showStatusFailure('Error: Title too long');
            return;
        }
        if (new Blob([$description.val()]).size > $description.attr('maxlength')) {
            showStatusFailure('Error: Description too long');
            return;
        }

        $.post('/request/achievement/update-base.php', {
            achievement: <?= $achievementID ?>,
            title: $title.val(),
            description: $description.val(),
            points: $('#pointsinput').val(),
        })
            .done(function () {
                location.reload();
            });
    }
    </script>
<?php endif ?>

<?php if ($permissions >= Permissions::Developer): ?>
    <script>
    function PostEmbedUpdate() {
        var url = $('#embedurlinput').val();

        showStatusMessage('Updating...');
        $.post('/request/achievement/update-video.php', {
            achievement: <?= $achievementID ?>,
            video: url
        })
            .done(function () {
                location.reload();
            });
    }

    function updateAchievementTypeFlag(typeFlag) {
        if (!confirm(`Are you sure you want to ${(typeFlag === <?= AchievementType::OfficialCore ?> ? 'promote' : 'demote')} these achievements?`)) {
            return;
        }
        showStatusMessage('Updating...');
        $.post('/request/achievement/update-flag.php', {
            achievements: <?= $achievementID ?>,
            flag: typeFlag,
        })
            .done(function () {
                location.reload();
            });
    }
    </script>
<?php endif ?>

<?php if ($achievedLocal): ?>
    <script>
    function ResetProgress() {
        if (confirm('Are you sure you want to reset this progress?')) {
            showStatusMessage('Updating...');

            $.post('/request/user/reset-achievements.php', {
                achievement: <?= $achievementID ?>
            })
                .done(function () {
                    location.reload();
                });
        }
    }
    </script>
<?php endif ?>

<div id="mainpage">
    <div id="leftcontainer">
        <?php
        echo "<div id='achievement'>";

        echo "<div class='navpath'>";
        echo "<a href='/gameList.php'>All Games</a>";
        echo " &raquo; <a href='/gameList.php?c=$consoleID'>$consoleName</a>";
        echo " &raquo; <a href='/game/$gameID'>$gameTitle</a>";
        echo " &raquo; <b>$achievementTitle</b>";
        echo "</div>"; // navpath

        echo "<h3>$gameTitle ($consoleName)</h3>";

        $fileSuffix = ($user == "" || !$achievedLocal) ? '_lock' : '';
        $badgeFullPath = media_asset("Badge/$badgeName$fileSuffix.png");

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

        echo "<div class='flex justify-between'>";
        echo "<div>";
        echo "<a href='/achievement/$achievementID'><strong>$achievementTitle</strong></a> ($achPoints)<span class='TrueRatio'> ($achTruePoints)</span><br>";
        echo "$desc";
        echo "</div>";
        if ($achievedLocal) {
            $niceDateWon = date("d M, Y H:i", strtotime($dateWonLocal));
            echo "<div class='text-right' class='smalldate'>Unlocked on<br>$niceDateWon</div>";
        }
        echo "</div>";

        echo "</div>";
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

        echo "<p class='embedded smalldata mb-3'>";
        echo "<small>";
        if ($achFlags === AchievementType::Unofficial) {
            echo "<b>Unofficial Achievement</b><br>";
        }
        echo "Created by ";
        RenderUserLink($author, LinkStyle::Text);
        echo " on: $niceDateCreated<br>Last modified: $niceDateModified<br>";
        echo "</small>";
        echo "</p>";

        echo "<p class='mb-2'>Won by <b>$numWinners</b> of <b>$numPossibleWinners</b> possible players ($recentWinnersPct%)</p>";

        if (isset($user) && $permissions >= Permissions::Registered) {
            $countTickets = countOpenTicketsByAchievement($achievementID);
            echo "<div class='flex justify-between mb-2'>";
            if ($countTickets > 0) {
                echo "<a href='/ticketmanager.php?a=$achievementID'>$countTickets open tickets</a>";
            } else {
                echo "<i>No open tickets</i>";
            }
            if (isAllowedToSubmitTickets($user)) {
                echo "<a class='btn btn-link' href='/reportissue.php?i=$achievementID'>Report an issue</a>";
            }
            echo "</div>";
        }

        if ($achievedLocal) {
            echo "<div class='devbox mb-3'>";
            echo "<span onclick=\"$('#resetboxcontent').toggle(); return false;\">Reset Progress ▼</span>";
            echo "<div id='resetboxcontent' style='display: none'>";
            echo "<button class='btn btn-danger' type='button' onclick='ResetProgress()'>Reset this achievement</button>";
            echo "</div></div>";
        }
        echo "<br>";

        if (isset($user) && $permissions >= Permissions::JuniorDeveloper) {
            echo "<div class='devbox mb-3'>";
            echo "<span onclick=\"$('#devboxcontent').toggle(); return false;\">Dev ▼</span>";
            echo "<div id='devboxcontent' style='display: none'>";

            if ($permissions >= Permissions::Developer || ($isSoleAuthor && $permissions >= Permissions::JuniorDeveloper && $achFlags === AchievementType::Unofficial)) {
                echo "<div>Update achievement details:</div>";
                echo "<table><tbody>";
                echo "<tr><td>Title:</td><td style='width:100%'><input id='titleinput' type='text' name='t' value='" . attributeEscape($achievementTitle) . "' style='width:100%' maxlength='64'></td></tr>";
                echo "<tr><td>Description:</td><td><input id='descriptioninput' type='text' name='d' value='" . attributeEscape($desc) . "' style='width:100%' maxlength='255'></td></tr>";
                echo "<tr><td>Points:</td><td>";
                echo "<select id='pointsinput' name='p'>";
                foreach (AchievementPoints::cases() as $pointsOption) {
                    echo "<option value='$pointsOption' " . ($achPoints === $pointsOption ? 'selected' : '') . ">$pointsOption</option>";
                }
                echo "</select>";
                echo "</td></tr>";
                echo "</tbody></table>";
                echo "&nbsp;<input type='submit' style='float: right;' value='Update' onclick=\"updateAchievementDetails()\" /><br><br>";

                echo "<form class='mb-2' method='post' action='/request/achievement/update-image.php' enctype='multipart/form-data'>";
                echo csrf_field();
                echo "<label>Badge<br>";
                echo "<input type='hidden' name='achievement' value='$achievementID'>";
                echo "<input type='file' accept='.png,.jpg,.gif' name='file'>";
                echo "</label>";
                echo "<input type='submit' name='submit' style='float: right' value='Submit'>";
                echo "</form><br>";
            }

            if ($permissions >= Permissions::Developer) {
                echo "<div class='devbox'>";
                echo "<div><span onclick=\"$('#embedcontent').toggle(); return false;\">Embedded video URL - show accepted formats ▼</span></div>";
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
                echo "<input type='hidden' name='a' value='$achievementID' />";
                echo "<input type='hidden' name='f' value='2' />";
                echo "<tr><td>Embed:</td><td style='width:100%'><input id='embedurlinput' type='text' name='v' value='$embedVidURL' style='width:100%;'/></td></tr>";
                echo "</tbody></table>";
                echo "&nbsp;<input type='submit' style='float: right;' value='Submit' onclick=\"PostEmbedUpdate()\" /><br><br>";

                if ($achFlags === AchievementType::OfficialCore) {
                    echo "<li>State: Official&nbsp;<button class='btn btn-danger' type='button' onclick='updateAchievementTypeFlag(" . AchievementType::Unofficial . ")'>Demote To Unofficial</button></li>";
                }
                if ($achFlags === AchievementType::Unofficial) {
                    echo "<li>State: Unofficial&nbsp;<button class='btn' type='button' onclick='updateAchievementTypeFlag(" . AchievementType::OfficialCore . ")'>Promote To Official</button></li>";
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
        echo "<div>";
        echo "<h3>Recent Unlocks</h3>";
        if (empty($winnerInfo)) {
            echo "Nobody yet! Will you be the first?!<br>";
        } else {
            echo "<table><tbody>";
            echo "<tr><th></th><th>User</th><th>Mode</th><th>Unlocked</th></tr>";
            $iter = 0;
            foreach ($winnerInfo as $userObject) {
                $userWinner = $userObject['User'];
                if ($userWinner == null || $userObject['DateAwarded'] == null) {
                    continue;
                }
                $niceDateWon = date("d M, Y H:i", strtotime($userObject['DateAwarded']));
                echo "<tr>";
                echo "<td class='w-[32px]'>";
                RenderUserLink($userWinner, LinkStyle::MediumImageWithText);
                echo "</td>";
                echo "<td>";
                if ($userObject['HardcoreMode']) {
                    echo "Hardcore";
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
<?php RenderContentEnd(); ?>

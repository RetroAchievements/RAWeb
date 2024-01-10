<?php

use App\Community\Enums\ArticleType;
use App\Platform\Enums\AchievementFlag;
use App\Platform\Enums\AchievementPoints;
use App\Platform\Enums\AchievementType;
use App\Platform\Models\PlayerAchievement;
use App\Site\Enums\Permissions;
use App\Site\Models\User;
use App\Support\Shortcode\Shortcode;
use Illuminate\Support\Facades\Blade;

authenticateFromCookie($user, $permissions, $userDetails);

$achievementID = (int) request('achievement');
if (empty($achievementID)) {
    abort(404);
}

$dataOut = GetAchievementData($achievementID);
if (empty($dataOut)) {
    abort(404);
}

$achievementTitle = $dataOut['Title'];
$desc = $dataOut['Description'];
$achFlags = (int) $dataOut['Flags'];
$achPoints = (int) $dataOut['Points'];
$achType = $dataOut['type'];
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
$isAuthor = $user == $author;

$canEmbedVideo = (
    $permissions >= Permissions::Developer
    || ($permissions === Permissions::JuniorDeveloper && $isAuthor)
);

$achievementTitleRaw = $dataOut['AchievementTitle'];
$achievementDescriptionRaw = $dataOut['Description'];
$gameTitleRaw = $dataOut['GameTitle'];

$parentGameID = getParentGameIdFromGameTitle($gameTitle, $consoleID);

sanitize_outputs(
    $achievementTitle,
    $desc,
    $gameTitle,
    $consoleName,
    $author
);

$numLeaderboards = getLeaderboardsForGame($gameID, $lbData, $user);

$numWinners = 0;
$numWinnersHardcore = 0;
$numPossibleWinners = 0;

$unlocks = getAchievementUnlocksData(
    $achievementID,
    $user,
    $numWinners,
    $numWinnersHardcore,
    $numPossibleWinners,
    $parentGameID,
    0,
    50
);

$trackedUnlocksUsers = User::whereIn('User', $unlocks->pluck('User')->unique())
    ->where('Untracked', false)
    ->pluck('User');

$unlocks = $unlocks->filter(function ($unlock) use ($trackedUnlocksUsers) {
    return $trackedUnlocksUsers->contains($unlock['User']);
});

$dataOut['NumAwarded'] = $numWinners;
$dataOut['NumAwardedHardcore'] = $numWinnersHardcore;

$dateWonLocal = "";
foreach ($unlocks as $userObject) {
    if ($userObject['User'] == $user) {
        $dateWonLocal = $userObject['DateAwarded'];

        if ($userObject['HardcoreMode'] === 1) {
            $dataOut['DateEarnedHardcore'] = $dateWonLocal;
        } else {
            $dataOut['DateEarned'] = $dateWonLocal;
        }

        break;
    }
}

if ($dateWonLocal === "" && isset($user)) {
    $userModel = User::firstWhere('User', $user);
    if ($userModel) {
        $playerAchievement = PlayerAchievement::where('user_id', $userModel->id)
            ->where('achievement_id', $achievementID)
            ->first();
        if ($playerAchievement) {
            if ($playerAchievement->unlocked_hardcore_at) {
                $dateWonLocal = $playerAchievement->unlocked_hardcore_at->__toString();
                $dataOut['DateEarnedHardcore'] = $dateWonLocal;
            } elseif ($playerAchievement->unlocked_at) {
                $dateWonLocal = $playerAchievement->unlocked_at->__toString();
                $dataOut['DateEarned'] = $dateWonLocal;
            }
        }
    }
}

$achievedLocal = ($dateWonLocal !== "");

$numArticleComments = getRecentArticleComments(ArticleType::Achievement, $achievementID, $commentData);

getCodeNotes($gameID, $codeNotes);

$pageTitle = "$achievementTitleRaw in $gameTitleRaw ($consoleName)";

function generateAchievementMetaDescription(
    string $achievementDescription,
    ?string $achievementType,
    string $gameTitle,
    string $consoleName,
    int $points = 0,
    int $winnerCount = 0,
): string {
    $typeLabel = $achievementType ? __("achievement-type.{$achievementType}") : null;
    $pointsLabel = $points === 1 ? "point" : "points";
    $localizedWinnerCount = localized_number($winnerCount);
    $winnerCountLabel = $winnerCount === 1 ? "player" : "players";

    $bracketText = "$points $pointsLabel";
    if ($typeLabel) {
        $bracketText .= ", $typeLabel";
    }

    return "$achievementDescription [$bracketText], won by $localizedWinnerCount $winnerCountLabel - $gameTitle for $consoleName";
}

RenderOpenGraphMetadata(
    $pageTitle,
    "achievement",
    media_asset("/Badge/$badgeName.png"),
    generateAchievementMetaDescription($achievementDescriptionRaw, $achType, $gameTitleRaw, $consoleName, $achPoints, $numWinners)
);
RenderContentStart($pageTitle);
?>
<?php if ($permissions >= Permissions::Developer || ($permissions >= Permissions::JuniorDeveloper && $isAuthor)): ?>
    <?php
        $canHaveBeatenTypes = (
            mb_strpos($gameTitle, "[Subset") === false
            && mb_strpos($gameTitle, "~Test Kit~") === false
            && $consoleID != 101
        );
    ?>

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
            type: $('#typeinput').val(),
        })
            .done(function () {
                location.reload();
            });
    }
    </script>
<?php endif ?>

<?php if ($canEmbedVideo): ?>
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

    /**
     * @param {3 | 5} newFlag - see AchievementFlag.php
     */
    function updateAchievementFlag(newFlag) {
        if (!confirm(`Are you sure you want to ${(newFlag === <?= AchievementFlag::OfficialCore ?> ? 'promote' : 'demote')} these achievements?`)) {
            return;
        }
        showStatusMessage('Updating...');
        $.post('/request/achievement/update-flag.php', {
            achievements: <?= $achievementID ?>,
            flag: newFlag,
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
<article>
    <?php
    echo "<div id='achievement'>";

    $breadcrumbAchievementTitle = Blade::render('
        <x-achievement.title
            :rawTitle="$rawTitle"
        />', [
        'rawTitle' => $achievementTitle,
    ]);

    echo "<div class='navpath'>";
    echo renderGameBreadcrumb($dataOut);
    echo " &raquo; <b>" . $breadcrumbAchievementTitle . "</b>";
    echo "</div>";

    echo Blade::render('
        <x-game.heading
            :consoleId="$consoleId"
            :consoleName="$consoleName"
            :gameTitle="$gameTitle"
        />
    ', [
        'consoleId' => $consoleID,
        'consoleName' => $consoleName,
        'gameTitle' => $gameTitle,
    ]);

    echo Blade::render('
        <x-game.achievements-list.achievements-list-item
            :achievement="$achievement"
            :isCreditDialogEnabled="$isCreditDialogEnabled"
            :totalPlayerCount="$totalPlayerCount"
            :isUnlocked="$isUnlocked"
        />
    ', [
        'achievement' => $dataOut,
        'isCreditDialogEnabled' => false,
        'totalPlayerCount' => $numPossibleWinners,
        'isUnlocked' => $achievedLocal,
    ]);

    $niceDateCreated = date("d M, Y H:i", strtotime($dateCreated));
    $niceDateModified = date("d M, Y H:i", strtotime($dateModified));

    echo "<p class='embedded smalldata mb-3'>";
    echo "<small>";
    if ($achFlags === AchievementFlag::Unofficial) {
        echo "<b>Unofficial Achievement</b><br>";
    }
    echo "Created by " . userAvatar($author, icon: false) . " on: $niceDateCreated<br>Last modified: $niceDateModified<br>";
    echo "</small>";
    echo "</p>";

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

        if ($permissions >= Permissions::Developer || $isAuthor) {
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

            $typeHelperContent = "A game is considered beaten if ALL " . __('achievement-type.' . AchievementType::Progression) . " achievements are unlocked and ANY " . __('achievement-type.' . AchievementType::WinCondition) . " achievements are unlocked.";
            echo "<tr><td>";
            if ($canHaveBeatenTypes) {
                echo "<label class='cursor-help flex items-center gap-x-1' for='typeinput' title='$typeHelperContent' aria-label='Type, $typeHelperContent'>";
                echo "Type";
                echo "<span>";
                echo Blade::render("<x-fas-info-circle class='w-5 h-5' aria-hidden='true' />");
                echo ":";
                echo "</span>";
                echo "</label>";
            } else {
                echo "<label for='typeinput'>Type:</label>";
            }
            echo "</td><td>";
            echo "<select id='typeinput' name='k'>";
            echo "<option value=''>None</option>";
            $allTypes = AchievementType::cases();
            $validTypes = $canHaveBeatenTypes
                ? $allTypes
                : array_filter($allTypes, function ($type) {
                    return $type !== AchievementType::Progression && $type !== AchievementType::WinCondition;
                });
            foreach ($validTypes as $typeOption) {
                echo "<option value='$typeOption' " . ($achType === $typeOption ? 'selected' : '') . ">";
                echo __('achievement-type.' . $typeOption);
                echo "</option>";
            }
            echo "</select></td></tr>";

            echo "</tbody></table>";
            echo "&nbsp;<button type='button' class='btn' style='float: right;' onclick=\"updateAchievementDetails()\">Update</button><br><br>";

            echo "<form class='mb-2' method='post' action='/request/achievement/update-image.php' enctype='multipart/form-data'>";
            echo csrf_field();
            echo "<label>Badge<br>";
            echo "<input type='hidden' name='achievement' value='$achievementID'>";
            echo "<input type='file' accept='.png,.jpg,.gif' name='file'>";
            echo "</label>";
            echo "<button class='btn' style='float: right'>Submit</button>";
            echo "</form><br>";
        }

        if ($canEmbedVideo) {
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
            echo "&nbsp;<button class='btn' style='float: right;' onclick=\"PostEmbedUpdate()\">Submit</button><br><br>";

            if ($achFlags === AchievementFlag::OfficialCore) {
                echo "<li>State: Official&nbsp;<button class='btn btn-danger' type='button' onclick='updateAchievementFlag(" . AchievementFlag::Unofficial . ")'>Demote To Unofficial</button></li>";
            }
            if ($achFlags === AchievementFlag::Unofficial) {
                echo "<li>State: Unofficial&nbsp;<button class='btn' type='button' onclick='updateAchievementFlag(" . AchievementFlag::OfficialCore . ")'>Promote To Official</button></li>";
            }
        }

        echo "<li> Achievement ID: " . $achievementID . "</li>";

        echo "<div>";

        $len = strlen($achMem);
        if ($len == 65535) {
            echo "<li>Mem:<span class='text-danger'> ⚠️ Max length definition is likely truncated and may not function as expected ⚠️ </span></li>";
        } else {
            echo "<li>Mem:</li>";
        }

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

    /*
     * id attribute used for scraping. NOTE: this will be deprecated. Use API_GetAchievementUnlocks instead
     */
    echo "<div>";
    echo "<h3>Recent Unlocks</h3>";
    if ($unlocks->isEmpty()) {
        echo "Nobody yet! Will you be the first?!<br>";
    } else {
        echo "<table class='table-highlight'><tbody>";
        echo "<tr class='do-not-highlight'><th class='w-[50%] xl:w-[60%]'>User</th><th>Mode</th><th class='text-right'>Unlocked</th></tr>";
        $iter = 0;
        foreach ($unlocks as $userObject) {
            $userWinner = $userObject['User'];
            if ($userWinner == null || $userObject['DateAwarded'] == null) {
                continue;
            }
            $niceDateWon = date("d M, Y H:i", strtotime($userObject['DateAwarded']));
            echo "<tr>";
            echo "<td class='w-[32px]'>";
            echo userAvatar($userWinner, iconClass: 'mr-2');
            echo "</td>";
            echo "<td>";
            if ($userObject['HardcoreMode']) {
                echo "Hardcore";
            }
            echo "</td>";
            echo "<td class='text-right'>$niceDateWon</td>";
            echo "</tr>";
        }

        echo "</tbody></table>";
    }
    echo "</div>";
    ?>
</article>
<?php view()->share('sidebar', true) ?>
<aside>
    <?php
    if ($user !== null) {
        // FIXME: https://discord.com/channels/476211979464343552/1026595325038833725/1162746245996093450
        // RenderPointsRankingComponent($user, true);
    }
    RenderGameLeaderboardsComponent($lbData, null);
    ?>
</aside>
<?php RenderContentEnd(); ?>

<?php

use RA\Permissions;

if (!authenticateFromCookie($user, $permissions, $userDetails, Permissions::Admin)) {
    abort(401);
}

$action = requestInputSanitized('action');
$message = null;
switch ($action) {
    case 'getachids':
        $gameIDs = separateList(requestInputSanitized('g'));
        foreach ($gameIDs as $nextGameID) {
            $ids = getAchievementIDsByGame($nextGameID);
            $message = implode(', ', $ids["AchievementIDs"] ?? []);
        }
        break;
    case 'getWinnersOfAchievements':
        $achievementIDs = requestInputSanitized('a', 0, 'string');
        $startTime = requestInputSanitized('s', null, 'string');
        $endTime = requestInputSanitized('e', null, 'string');
        $hardcoreMode = requestInputSanitized('h', 0, 'integer');
        $dateString = "";
        if (isset($achievementIDs)) {
            if (strtotime($startTime)) {
                if (strtotime($endTime)) {
                    // valid start and end
                    $dateString = " between $startTime and $endTime";
                } else {
                    // valid start, invalid end
                    $dateString = " since $startTime";
                }
            } else {
                if (strtotime($endTime)) {
                    // invalid start, valid end
                    $dateString = " before $endTime";
                }
            }

            $winners = getUnlocksInDateRange(separateList($achievementIDs), $startTime, $endTime, (int) $hardcoreMode);

            $keys = array_keys($winners);
            for ($i = 0; $i < count($winners); $i++) {
                $winnersCount = is_countable($winners[$keys[$i]]) ? count($winners[$keys[$i]]) : 0;
                $message .= "<strong>" . number_format($winnersCount) . " Winners of " . $keys[$i] . " in " . ($hardcoreMode ? "Hardcore mode" : "Softcore mode") . "$dateString:</strong><br>";
                $message .= implode(', ', $winners[$keys[$i]]) . "<br><br>";
            }
        }

        break;
    case 'giveaward':
        $awardAchievementID = requestInputSanitized('a');
        $awardAchievementUser = requestInputSanitized('u');
        $awardAchHardcore = requestInputSanitized('h', 0, 'integer');

        if (isset($awardAchievementID) && isset($awardAchievementUser)) {
            $usersToAward = preg_split('/\W+/', $awardAchievementUser);
            foreach ($usersToAward as $nextUser) {
                $validUser = validateUsername($nextUser);
                if (!$validUser) {
                    $message .= "<strong>$nextUser</strong>: user not found!<br>";
                    continue;
                }
                $message .= "<strong>$validUser</strong>:<br>";
                $ids = separateList($awardAchievementID);
                foreach ($ids as $nextID) {
                    $message .= "- $nextID: ";
                    $awardResponse = unlockAchievement($validUser, $nextID, $awardAchHardcore);
                    if (empty($awardResponse) || !$awardResponse['Success']) {
                        $message .= array_key_exists('Error', $awardResponse)
                            ? $awardResponse['Error']
                            : "Failed to award achievement!";
                    } else {
                        $message .= "Awarded achievement";
                    }
                    $message .= "<br>";
                }
                recalculatePlayerPoints($validUser);

                $hardcorePoints = 0;
                $softcorePoints = 0;
                if (getPlayerPoints($validUser, $userPoints)) {
                    $hardcorePoints = $userPoints['RAPoints'];
                    $softcorePoints = $userPoints['RASoftcorePoints'];
                }
                $message .= "- Recalculated Score: $hardcorePoints <span class='softcore'>($softcorePoints softcore)</span><br>";
            }
        }
        break;
    case 'updatestaticdata':
        $aotwAchID = requestInputSanitized('a', 0, 'integer');
        $aotwForumID = requestInputSanitized('f', 0, 'integer');
        $aotwStartAt = requestInputSanitized('s', null, 'string');

        $query = "UPDATE StaticData SET
            Event_AOTW_AchievementID='$aotwAchID',
            Event_AOTW_ForumID='$aotwForumID',
            Event_AOTW_StartAt='$aotwStartAt'";

        $db = getMysqliConnection();
        $result = s_mysql_query($query);

        if ($result) {
            $message = "Successfully updated static data!";
        } else {
            $message = mysqli_error($db);
        }

        break;
}

$staticData = getStaticData();

RenderContentStart('Admin Tools');
?>
<script src="/vendor/jquery.datetimepicker.full.min.js"></script>
<link rel="stylesheet" href="/vendor/jquery.datetimepicker.min.css">
<div id="mainpage" class="flex-wrap">
    <?php if ($message): ?>
        <div class="w-full">
            <?= $message ?>
        </div>
    <?php endif ?>

    <?php if ($permissions >= Permissions::Admin) : ?>
        <div id="fullcontainer" class="w-full">
            <h4>Get Game Achievement IDs</h4>
            <form method="post" action="admin.php">
                <?= csrf_field() ?>
                <table class="mb-1">
                    <colgroup>
                        <col>
                        <col class="w-full">
                    </colgroup>
                    <tbody>
                    <tr>
                        <td class="whitespace-nowrap">
                            <label for="achievements_game_id">Game ID</label>
                        </td>
                        <td>
                            <input id="achievements_game_id" name="g">
                        </td>
                    </tr>
                    </tbody>
                </table>
                <input type="hidden" name="action" value="getachids">
                <input type="submit" value="Submit">
            </form>
        </div>

        <div id="fullcontainer" class="w-full">
            <?php
            $winnersStartTime = $staticData['winnersStartTime'] ?? null;
            $winnersEndTime = $staticData['winnersEndTime'] ?? null;
            ?>
            <h4>Get Winners of Achievements</h4>
            <form method="post" action="admin.php">
                <?= csrf_field() ?>
                <table class="mb-1">
                    <colgroup>
                        <col>
                        <col>
                        <col>
                        <col class="w-full">
                    </colgroup>
                    <tbody>
                    <tr>
                        <td class="whitespace-nowrap">
                            <label for="winnersAchievementIDs">Achievement IDs</label>
                        </td>
                        <td>
                            <input id="winnersAchievementIDs" name="a">
                        </td>
                        <td></td>
                        <td></td>
                    </tr>
                    <tr>
                        <td class="whitespace-nowrap">
                            <label for="startTime">Start At (UTC time)</label>
                        </td>
                        <td>
                            <input id="startTime" name="s" value="<?= $winnersStartTime ?>">
                        </td>
                        <td class="whitespace-nowrap">
                            <label for="endTime">End At (UTC time)</label>
                        </td>
                        <td>
                            <input id="endTime" name="e" value="<?= $winnersEndTime ?>">
                        </td>
                    </tr>
                    <tr>
                        <td class="whitespace-nowrap">
                            <label for="hardcoreWinners">Hardcore winners?</label>
                        </td>
                        <td>
                            <input id="hardcoreWinners" type="checkbox" name="h" value="1">
                        </td>
                        <td></td>
                        <td></td>
                    </tr>
                    </tbody>
                </table>
                <input type="hidden" name="action" value="getWinnersOfAchievements">
                <input type="submit" value="Submit">
                <button class="btn">Submit</button>
            </form>

            <script>
            jQuery('#startTime').datetimepicker({
                format: 'Y-m-d H:i:s',
                mask: true, // '9999/19/39 29:59' - digit is the maximum possible for a cell
            });
            jQuery('#endTime').datetimepicker({
                format: 'Y-m-d H:i:s',
                mask: true, // '9999/19/39 29:59' - digit is the maximum possible for a cell
            });
            </script>
        </div>

        <div id="fullcontainer" class="w-full">
            <h4>Award Achievement</h4>
            <form method="post" action="admin.php">
                <?= csrf_field() ?>
                <table class="mb-1">
                    <colgroup>
                        <col>
                        <col class="w-full">
                    </colgroup>
                    <tbody>
                    <tr>
                        <td class="whitespace-nowrap">
                            <label for="award_achievement_user">User to receive achievement</label>
                        </td>
                        <td>
                            <input id="award_achievement_user" name="u">
                        </td>
                    </tr>
                    <tr>
                        <td class="whitespace-nowrap">
                            <label for="award_achievement_id">Achievement IDs</label>
                        </td>
                        <td>
                            <input id="award_achievement_id" name="a">
                        </td>
                    </tr>
                    <tr>
                        <td class="whitespace-nowrap">
                            <label for="award_achievement_hardcore">Include hardcore?</label>
                        </td>
                        <td>
                            <input id="award_achievement_hardcore" type="checkbox" name="h" value="1">
                        </td>
                    </tr>
                    </tbody>
                </table>
                <input type="hidden" name="action" value="giveaward">
                <button class="btn btn-danger">Submit</button>
            </form>
        </div>

        <div id="fullcontainer" class="w-full">
            <?php
            $eventAotwAchievementID = $staticData['Event_AOTW_AchievementID'] ?? null;
            $eventAotwStartAt = $staticData['Event_AOTW_StartAt'] ?? null;
            $eventAotwForumTopicID = $staticData['Event_AOTW_ForumID'] ?? null;
            ?>
            <h4>Achievement of the Week</h4>
            <form method="post" action="admin.php">
                <?= csrf_field() ?>
                <table class="mb-1">
                    <colgroup>
                        <col>
                        <col>
                        <col class="w-full">
                    </colgroup>
                    <tbody>
                    <tr>
                        <td class="whitespace-nowrap">
                            <label for="event_aotw_achievement_id">Achievement ID</label>
                        </td>
                        <td>
                            <input id="event_aotw_achievement_id" name="a" value="<?= $eventAotwAchievementID ?>">
                        </td>
                        <td>
                            <a href="/achievement/<?= $eventAotwAchievementID ?>">Link</a>
                        </td>
                    </tr>
                    <tr>
                        <td class="whitespace-nowrap">
                            <label for="event_aotw_start_at">Start At (UTC time)</label>
                        </td>
                        <td>
                            <input id="event_aotw_start_at" name="s" value="<?= $eventAotwStartAt ?>">
                        </td>
                        <td>
                        </td>
                    </tr>
                    <tr>
                        <td class="whitespace-nowrap">
                            <label for="event_aotw_forum_topic_id">Forum Topic ID</label>
                        </td>
                        <td>
                            <input id="event_aotw_forum_topic_id" name="f" value="<?= $eventAotwForumTopicID ?>">
                        </td>
                        <td>
                            <a href="/viewtopic.php?t=<?= $eventAotwForumTopicID ?>">Link</a>
                        </td>
                    </tr>
                    </tbody>
                </table>
                <input type="hidden" name="action" value="updatestaticdata">
                <button class="btn btn-primary">Submit</button>
            </form>

            <div id="aotw_entries"></div>

            <script>
                jQuery('#event_aotw_start_at').datetimepicker({
                    format: 'Y-m-d H:i:s',
                    mask: true, // '9999/19/39 29:59' - digit is the maximum possible for a cell
                });
            </script>
        </div>
    <?php endif ?>
</div>
<?php RenderContentEnd(); ?>

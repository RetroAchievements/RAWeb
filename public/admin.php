<?php

use App\Platform\Models\Achievement;
use App\Site\Enums\Permissions;
use App\Site\Models\StaticData;

if (!authenticateFromCookie($user, $permissions, $userDetails, Permissions::Admin)) {
    abort(401);
}

$action = request()->input('action');
$message = null;
if ($action === 'achievement-ids') {
    $gameIDs = separateList(request()->query('g'));
    $achievementIds = collect();
    foreach ($gameIDs as $gameId) {
        $achievementIds = $achievementIds->merge(
            Achievement::where('GameID', $gameId)->published()->pluck('ID')
        );
    }
    $message = $achievementIds->implode(', ');
}

if ($action === 'unlocks') {
    $achievementIDs = request()->query('a');
    $startTime = request()->query('s');
    $endTime = request()->query('e');
    $hardcoreMode = (int) request()->query('h');
    $dateString = "";
    if (isset($achievementIDs)) {
        if (strtotime($startTime)) {
            $dateString = strtotime($endTime) ? " between $startTime and $endTime" : " since $startTime";
        } else {
            if (strtotime($endTime)) {
                // invalid start, valid end
                $dateString = " before $endTime";
            }
        }

        $winners = getUnlocksInDateRange(separateList($achievementIDs), $startTime, $endTime, $hardcoreMode);

        $keys = array_keys($winners);
        $winnersCount = count($winners);
        foreach ($winners as $key => $winner) {
            $winnersCount = is_countable($winner) ? count($winner) : 0;
            $message .= "<strong>" . number_format($winnersCount) . " Winners of " . $key . " in " . ($hardcoreMode ? "Hardcore mode" : "Softcore mode") . "$dateString:</strong><br>";
            $message .= implode(', ', $winner) . "<br><br>";
        }
    }
}

if ($action === 'manual-unlock') {
    $awardAchievementID = requestInputSanitized('a');
    $awardAchievementUser = requestInputSanitized('u');
    $awardAchHardcore = requestInputSanitized('h', 0, 'integer');

    if (isset($awardAchievementID) && isset($awardAchievementUser)) {
        $usersToAward = preg_split('/\W+/', $awardAchievementUser);
        foreach ($usersToAward as $nextUser) {
            $validUser = validateUsername($nextUser);
            if (!$validUser) {
                continue;
            }
            $ids = separateList($awardAchievementID);
            foreach ($ids as $nextID) {
                $awardResponse = unlockAchievement($validUser, $nextID, $awardAchHardcore);
            }
            recalculatePlayerPoints($validUser);

            $hardcorePoints = 0;
            $softcorePoints = 0;
            if (getPlayerPoints($validUser, $userPoints)) {
                $hardcorePoints = $userPoints['RAPoints'];
                $softcorePoints = $userPoints['RASoftcorePoints'];
            }
        }

        return back()->with('success', __('legacy.success.ok'));
    }

    return back()->withErrors(__('legacy.error.error'));
}

if ($action === 'aotw') {
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
        return back()->with('success', __('legacy.success.ok'));
    }

    return back()->withErrors(__('legacy.error.error'));
}

$staticData = StaticData::first();

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
            <form action="admin.php">
                <input type="hidden" name="action" value="achievement-ids">
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
                <button>Submit</button>
            </form>
        </div>

        <div id="fullcontainer" class="w-full">
            <?php
            $winnersStartTime = $staticData['winnersStartTime'] ?? null;
            $winnersEndTime = $staticData['winnersEndTime'] ?? null;
            ?>
            <h4>Get Achievement Unlocks</h4>
            <form action="admin.php">
                <input type="hidden" name="action" value="unlocks">
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
                <button>Submit</button>
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
            <h4>Unlock Achievement</h4>
            <form method="post" action="admin.php">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="manual-unlock">
                <table class="mb-1">
                    <colgroup>
                        <col>
                        <col class="w-full">
                    </colgroup>
                    <tbody>
                    <tr>
                        <td class="whitespace-nowrap">
                            <label for="award_achievement_user">User to unlock achievement</label>
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
                <button class="btn">Submit</button>
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
                <input type="hidden" name="action" value="aotw">
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
                <button class="btn">Submit</button>
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

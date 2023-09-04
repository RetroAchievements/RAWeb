<?php

use App\Platform\Models\Achievement;
use App\Site\Enums\Permissions;
use App\Site\Models\StaticData;
use App\Site\Models\User;
use Illuminate\Support\Facades\Blade;

if (!authenticateFromCookie($user, $permissions, $userDetails, Permissions::Moderator)) {
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
        $errors = [];
        foreach ($usersToAward as $nextUser) {
            $validUser = validateUsername($nextUser);
            if (!$validUser) {
                continue;
            }
            $ids = separateList($awardAchievementID);
            foreach ($ids as $nextID) {
                $awardResponse = unlockAchievement($validUser, $nextID, $awardAchHardcore);
                if (array_key_exists('Error', $awardResponse)) {
                    $errors[] = $awardResponse['Error'];
                }
            }
            recalculatePlayerPoints($validUser);

            $hardcorePoints = 0;
            $softcorePoints = 0;
            if (getPlayerPoints($validUser, $userPoints)) {
                $hardcorePoints = $userPoints['RAPoints'];
                $softcorePoints = $userPoints['RASoftcorePoints'];
            }
        }

        if (!empty($errors)) {
            return back()->withErrors(join('. ', $errors));
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

if ($action === 'alt_identifier') {
    $altsForUser = request()->input('u');

    $forUser = User::where('User', $altsForUser)->first();
    if ($forUser == null) {
        $message = "Unknown user: $altsForUser";
    } else {
        $altsForUser = $forUser->User;

        $emailAddresses = [];
        if (!empty($forUser->EmailAddress)) {
            $emailAddresses[] = $forUser->EmailAddress;
        }
        if (!empty($forUser->email_backup) && $forUser->email_backup != $forUser->EmailAddress) {
            $emailAddresses[] = $forUser->email_backup;
        }
        $message = "No alts found for $altsForUser";
        if (!empty($emailAddresses)) {
            $alts = User::withTrashed()
                ->select('User', 'Permissions', 'LastLogin', 'Deleted')
                ->where(function ($query) use ($emailAddresses) {
                    $query->whereIn('EmailAddress', $emailAddresses)
                          ->orWhereIn('email_backup', $emailAddresses);
                })
                ->orderBy('LastLogin', 'desc')
                ->get();

            $numAccounts = $alts->count();
            if ($numAccounts > 1) {
                $message = "<div class='mb-1'>";
                $message .= "$numAccounts users share the same email address as $altsForUser:";
                $message .= "</div>";

                $message .= "<div class='table-wrapper'><table class='table-highlight'><tbody>";
                $message .= "<tr class='do-not-highlight'>";
                $message .= "<th>User</th><th>Account Type</th><th>Last Login</th>";
                $message .= "</tr>";

                foreach ($alts as $alt) {
                    $message .= '<tr><td>';
                    $message .= userAvatar($alt['User']);
                    $message .= '</td><td>';
                    $message .= ($alt['Deleted']) ? 'Deleted' : Permissions::toString($alt['Permissions']);
                    $message .= '</td><td>';
                    $message .= !empty($alt['LastLogin']) ? getNiceDate(strtotime($alt['LastLogin'])) : '';
                    $message .= '</td></tr>';
                }
                $message .= '</tbody></table></div>';
            }
        }
    }
}

$staticData = StaticData::first();

RenderContentStart('Admin Tools');
?>
<script src="/vendor/jquery.datetimepicker.full.min.js"></script>
<link rel="stylesheet" href="/vendor/jquery.datetimepicker.min.css">
<?php if ($message): ?>
    <div class="w-full mb-6">
        <?= $message ?>
    </div>
<?php endif ?>

<?php if ($permissions >= Permissions::Moderator) : ?>
    <article>
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
            <button class="btn">Submit</button>
        </form>
    </article>

    <article>
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
    </article>

    <article>
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
    </article>

    <article>
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
    </article>

    <article>
        <h4>Alt Identifier</h4>
        <form action="admin.php">
            <input type="hidden" name="action" value="alt_identifier">
            <table class="mb-1">
                <colgroup>
                    <col>
                    <col class="w-full">
                </colgroup>
                <tbody>
                <tr>
                    <td class="whitespace-nowrap">
                        <label for="alts_of_user">User to query for alts</label>
                    </td>
                    <td>
                        <input id="alts_of_user" name="u">
                    </td>
                </tr>
                </tbody>
            </table>
            <button class="btn">Submit</button>
        </form>
    </article>

    <article>
        <h4>Feature Flags</h4>
        <?= Blade::render('<x-feature-flags />'); ?>
    </article>
<?php endif ?>
<?php RenderContentEnd(); ?>

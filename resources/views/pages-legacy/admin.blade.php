<?php

use App\Enums\Permissions;
use App\Models\StaticData;
use App\Models\User;
use App\Models\Achievement;

if (!authenticateFromCookie($user, $permissions, $userDetails, Permissions::Moderator)) {
    abort(401);
}

$action = request()->input('action');
$message = null;

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
?>
<x-app-layout pageTitle="Admin Tools">
<script src="/vendor/jquery.datetimepicker.full.min.js"></script>
<link rel="stylesheet" href="/vendor/jquery.datetimepicker.min.css">
<?php if ($message): ?>
    <div class="w-full mb-6">
        <?= $message ?>
    </div>
<?php endif ?>

<?php if ($permissions >= Permissions::Moderator) : ?>
    <section class="mb-4">
        <h4>Unlock Achievement</h4>
        <form method="post" action="request/admin.php">
            @csrf()
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
    </section>

    <section class="mb-4">
        <h4>Copy Unlocks</h4>
        <form method="post" action="request/admin.php">
            @csrf()
            <input type="hidden" name="action" value="copy-unlocks">
            <table class="mb-1">
                <colgroup>
                    <col>
                    <col class="w-full">
                </colgroup>
                <tbody>
                <tr>
                    <td class="whitespace-nowrap">
                        <label for="required_achievement_id" style="cursor:help"
                               title="CSV of achievements the player must have previously unlocked">Required achievement IDs</label>
                    </td>
                    <td>
                        <input id="required_achievement_id" name="s">
                    </td>
                </tr>
                <tr>
                    <td class="whitespace-nowrap">
                        <label for="award_achievement_id" style="cursor:help"
                               title="CSV of achievements that should be unlocked if the user has all of the required achievements unlocked">Unlock achievement IDs</label>
                    </td>
                    <td>
                        <input id="award_achievement_id" name="a">
                    </td>
                </tr>
                </tbody>
            </table>
            <button class="btn">Submit</button>
        </form>
    </section>

    <section class="mb-4">
        <h4>Migrate Achievements</h4>
        <form method="post" action="request/admin.php">
            @csrf()
            <input type="hidden" name="action" value="migrate-achievement">
            <table class="mb-1">
                <colgroup>
                    <col>
                    <col class="w-full">
                </colgroup>
                <tbody>
                <tr>
                    <td class="whitespace-nowrap">
                        <label for="achievement_id">Achievement IDs</label>
                    </td>
                    <td>
                        <input id="achievement_id" name="a">
                    </td>
                </tr>
                <tr>
                    <td class="whitespace-nowrap">
                        <label for="game_id">New game to transfer achievements to</label>
                    </td>
                    <td>
                        <input id="game_id" name="g">
                    </td>
                </tr>
                </tbody>
            </table>
            <button class="btn">Submit</button>
        </form>
    </section>

    <section class="mb-4">
        <?php
        $eventAotwAchievementID = $staticData['Event_AOTW_AchievementID'] ?? null;
        $eventAotwStartAt = $staticData['Event_AOTW_StartAt'] ?? null;
        $eventAotwForumTopicID = $staticData['Event_AOTW_ForumID'] ?? null;
        ?>
        <h4>Achievement of the Week</h4>
        <form method="post" action="request/admin.php">
            @csrf()
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
    </section>

    <section class="mb-4">
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
    </section>
<?php endif ?>
</x-app-layout>

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

    $forUser = User::whereName($altsForUser)->first();
    if ($forUser == null) {
        $message = "Unknown user: $altsForUser";
    } else {
        $altsForUser = $forUser->display_name === $forUser->username
            ? $forUser->display_name
            : "{$forUser->display_name} ({$forUser->username})";

        $emailAddresses = [];
        if (!empty($forUser->email)) {
            $emailAddresses[] = $forUser->email;
        }
        if (!empty($forUser->email_original) && $forUser->email_original != $forUser->email) {
            $emailAddresses[] = $forUser->email_original;
        }
        $message = "No alts found for $altsForUser";
        if (!empty($emailAddresses)) {
            $alts = User::withTrashed()
                ->select('id', 'username', 'Permissions', 'last_activity_at', 'deleted_at')
                ->where(function ($query) use ($emailAddresses) {
                    $query->whereIn('email', $emailAddresses)
                        ->orWhereIn('email_original', $emailAddresses);
                })
                ->orderBy('last_activity_at', 'desc')
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
                    $message .= userAvatar($alt['username']);
                    $message .= '</td><td>';
                    $message .= ($alt['deleted_at']) ? 'Deleted' : Permissions::toString($alt['Permissions']);
                    $message .= '</td><td>';
                    $message .= !empty($alt['last_activity_at']) ? getNiceDate(strtotime($alt['last_activity_at'])) : '';
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    const copyUnlocksForm = document.getElementById('copy-unlocks-form');
    if (copyUnlocksForm) {
        copyUnlocksForm.addEventListener('submit', function(e) {
            const requiredInput = document.getElementById('required_achievement_id');
            const awardInput = document.getElementById('copy_unlock_achievement_id');
            
            if (!requiredInput || !awardInput) {
                console.error('Could not find input elements');
                return;
            }
            
            const requiredIds = requiredInput.value.trim();
            const awardIds = awardInput.value.trim();
            
            if (!requiredIds || !awardIds) {
                alert('Please enter both required and unlock achievement IDs.');
                e.preventDefault();
                return;
            }
            
            const message = `Are you sure you want to copy unlocks?\nDOUBLE CHECK THESE IDs!\n\nFrom achievement IDs: ${requiredIds}\nTo achievement IDs: ${awardIds}`;
            
            if (!confirm(message)) {
                e.preventDefault();
            }
        });
    }
});
</script>

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
        <form method="post" action="request/admin.php" id="copy-unlocks-form">
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
                               title="CSV of achievements the player must have previously unlocked">From achievement IDs</label>
                    </td>
                    <td>
                        <input id="required_achievement_id" name="s">
                    </td>
                </tr>
                <tr>
                    <td class="whitespace-nowrap">
                        <label for="copy_unlock_achievement_id" style="cursor:help"
                               title="CSV of achievements that should be unlocked if the user has all of the required achievements unlocked">To achievement IDs</label>
                    </td>
                    <td>
                        <input id="copy_unlock_achievement_id" name="a">
                    </td>
                </tr>
                </tbody>
            </table>
            <button class="btn">Submit</button>
        </form>
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

<?php

use App\Enums\Permissions;
use App\Enums\UserPreference;
use App\Models\User;

if (!authenticateFromCookie($user, $permissions, $userDetails)) {
    abort(401);
}

// cookie only returns the most common account details. go get the rest
getAccountDetails($user, $userDetails);

$userModel = User::firstWhere('User', $user);

$points = (int) $userDetails['RAPoints'];
$websitePrefs = (int) $userDetails['websitePrefs'];
$emailAddr = $userDetails['EmailAddress'];
$permissions = (int) $userDetails['Permissions'];
$contribCount = (int) $userDetails['ContribCount'];
$contribYield = (int) $userDetails['ContribYield'];
$userWallActive = $userDetails['UserWallActive'];
$apiKey = $userDetails['APIKey'];
$userMotto = htmlspecialchars($userDetails['Motto']);
?>
<x-app-layout pageTitle="Settings">
<script>
function ShowLoadingIcon(iconRootId) {
    let iconRoot = document.getElementById(iconRootId);
    iconRoot.querySelector('.loadingicon-done').classList.add('hidden');
    iconRoot.querySelector('.loadingicon-spinner').classList.remove('hidden');
    iconRoot.querySelector('.loadingicon-spinner').classList.add('animate-spin');
    iconRoot.classList.remove('opacity-0');
}

function HideLoadingIcon(iconRootId) {
    let iconRoot = document.getElementById(iconRootId);
    let spinner = iconRoot.querySelector('.loadingicon-spinner');
    let doneIcon = iconRoot.querySelector('.loadingicon-done');

    if (!spinner.classList.contains('hidden')) {
        spinner.classList.add('hidden');
        spinner.classList.remove('animate-spin');
    }
    if (!doneIcon.classList.contains('hidden')) {
        doneIcon.classList.add('hidden');
    }

    iconRoot.classList.add('opacity-0');
}

function ShowDoneIcon(iconRootId) {
    let iconRoot = document.getElementById(iconRootId);
    iconRoot.querySelector('.loadingicon-done').classList.remove('hidden');
    iconRoot.querySelector('.loadingicon-spinner').classList.add('hidden');
    iconRoot.querySelector('.loadingicon-spinner').classList.remove('animate-spin');
    setTimeout(() => iconRoot.classList.add('opacity-0'), 750);
}

/**
 * @param {number} targetLoadingIcon - There are multiple loading icons on the page, which one will update based on this prefs change?
 */
function DoChangeUserPrefs(targetLoadingIcon = 1) {
    var newUserPrefs = 0;
    for (i = 0; i <= 17; ++i) {
        var checkbox = document.getElementById('UserPreference' + i);
        if (checkbox != null && checkbox.checked) {
            newUserPrefs += (1 << i);
        }
    }

    const loadingIconId = `loadingicon-${targetLoadingIcon}`;
    ShowLoadingIcon(loadingIconId);

    $.ajax({
        url: '{{ route("settings.preferences.update") }}',
        type: 'PUT',
        contentType: 'application/json; charset=utf-8',
        dataType: 'json',
        data: JSON.stringify({ websitePrefs: newUserPrefs }),
        success: () => {
            ShowDoneIcon(loadingIconId);
        }
    });
}

function UploadNewAvatar() {
    // New file
    var photo = document.getElementById('uploadimagefile');
    var file = photo.files[0];
    var reader = new FileReader();
    reader.onload = function () {
        ShowLoadingIcon('loadingiconavatar');
        
        const route = '{{ route("user.avatar.store") }}';
        $.post(route, { imageData: reader.result },
            function (data) {
                ShowDoneIcon('loadingiconavatar');

                var result = $.parseJSON(data);
                var d = new Date();
                $('.userpic').attr('src', '<?= media_asset('/UserPic/' . $user . '.png')  ?>' + '?' + d.getTime());
            })
            .fail(function() {
                HideLoadingIcon('loadingiconavatar');
            })
    };
    reader.readAsDataURL(file);
    return false;
}

function handleSetMotto(newMotto) {
    $.ajax({
        url: '{{ route('settings.profile.update') }}',
        type: 'PUT',
        data: { motto: newMotto },
        success: () => {
            showStatusSuccess('{{ __("legacy.success.change") }}');
        }
    });
}

function handleSetAllowComments() {
    const newValue = document.querySelector('#userwallactive').checked;

    $.ajax({
        url: '{{ route('settings.profile.update') }}',
        type: 'PUT',
        contentType: 'application/json; charset=utf-8',
        dataType: 'json',
        data: JSON.stringify({ userWallActive: newValue }),
        success: () => {
            showStatusSuccess('{{ __("legacy.success.change") }}');
        }
    });
}

function handleResetWebApiKeyClick() {
    if (!confirm('Are you sure you want to reset your web API key?')) {
        return;
    }

    $.ajax({
        url: '{{ route('settings.keys.web.destroy') }}',
        type: 'DELETE',
        success: () => {
            showStatusSuccess('{{ __("legacy.success.reset") }}');

            // Temporary, will be removed in the Inertia migration.
            setTimeout(() => {
                window.location.reload();
            }, 1000)
        }
    });
}

function handleResetConnectApiKeyClick() {
    if (!confirm('Are you sure you want to reset your Connect API key?')) {
        return;
    }

    $.ajax({
        url: '{{ route('settings.keys.connect.destroy') }}',
        type: 'DELETE',
        success: () => {
            showStatusSuccess('{{ _("legacy.success.reset") }}');
        }
    });
}

function handleDeleteAllUserComments() {
    if (!confirm('Are you sure you want to permanently delete all comment on your wall?')) {
        return;
    }

    $.ajax({
        url: '{{ route('user.comment.destroyAll', $userModel) }}',
        type: 'DELETE',
        success: () => {
            showStatusSuccess('{{ __("legacy.success.delete") }}');
        }
    });
}

function handleChangePasswordSubmit(formValues) {
    const { currentPassword, newPassword, confirmPassword } = formValues;

    if (newPassword !== confirmPassword) {
        alert("Make sure the New Password and Confirm Password values match.");
        
        return;
    }

    $.ajax({
        url: '{{ route('settings.password.update') }}',
        type: 'PUT',
        contentType: 'application/json; charset=utf-8',
        dataType: 'json',
        data: JSON.stringify({ currentPassword, newPassword }),
        success: () => {
            showStatusSuccess('{{ __("legacy.success.change") }}');
        }
    });
}

function handleChangeEmailSubmit(formValues) {
    const { newEmail, confirmEmail } = formValues;

    if (newEmail !== confirmEmail) {
        alert("Make sure the New Email Address and Confirm Email Address values match.");
        
        return;
    }

    const hasDevPermissions = {{ $permissions >= Permissions::Developer ? 'true' : 'false' }};
    if (
        hasDevPermissions
        && !confirm('Changing your email address will revoke your privileges and you will need to have them restored by staff. Are you sure you want to continue?')
    ) {
        return;
    }

    $.ajax({
        url: '{{ route('settings.email.update') }}',
        type: 'PUT',
        contentType: 'application/json; charset=utf-8',
        dataType: 'json',
        data: JSON.stringify({ newEmail }),
        success: () => {
            showStatusSuccess('{{ __("legacy.success.change") }}')
        }
    });
}

function handleRequestAccountDeletion() {
    if (!confirm('Are you sure you want to request account deletion?')) {
        return;
    }

    $.ajax({
        url: '{{ route('user.delete-request.store') }}',
        type: 'POST',
        success: () => {
            showStatusSuccess('{{ __("legacy.success.ok") }}');
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        }
    });
}

function handleCancelRequestAccountDeletion() {
    if(!confirm('Are you sure you want to cancel your account deletion request?')) {
        return;
    }

    $.ajax({
        url: '{{ route('user.delete-request.destroy') }}',
        type: 'DELETE',
        success: () => {
            showStatusSuccess('{{ __("legacy.success.ok") }}');
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        }
    });
}

function handleRemoveAvatar() {
    if (!confirm('Are you sure you want to permanently delete this avatar?')) {
        return;
    }

    $.ajax({
        url: '{{ route('user.avatar.destroy') }}',
        type: 'DELETE',
        success: () => {
            showStatusSuccess('{{ __("legacy.success.ok") }}');
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        }
    });
}
</script>

    <div class='detaillist'>
        <div class='component'>
            <h2>Profile</h2>
            <?php
            echo "<table><colgroup><col style='width: 300px'></colgroup><tbody>";
            echo "<tr>";
            echo "<td><label for='motto'>Roles</label></td>";
            echo "<td>";
            echo Permissions::toString($permissions);
            echo "</td>";
            echo "</tr>";
            if ($permissions >= Permissions::Registered) {
                $userMottoString = !empty($userMotto) ? $userMotto : "";
                echo "<tr>";
                echo "<td><label for='motto'>User Motto</label></td>";
                echo "<td>";

                echo <<<HTML
                    <form x-data="{ isValid: true, motto: '$userMottoString' }" class='flex gap-2 mb-1' @submit.prevent="handleSetMotto(motto)">
                        <div class="flex gap-x-2">
                            <div class="grid gap-y-1">
                                <input
                                    id="motto"
                                    name="motto"
                                    x-model="motto"
                                    value="$userMottoString"
                                    maxlength="50"
                                    size="50"
                                    placeholder="Your motto"
                                    x-on:input="isValid = window.getStringByteCount(\$event.target.value) <= 50"
                                >
                                <div class="text-xs flex w-full justify-between">
                                    <p>No profanity.</p>
                                    <div>
                                        <div class="textarea-counter" data-textarea-id="motto"></div>
                                        <div class="text-danger hidden"></div>
                                    </div>
                                </div>
                            </div>
                            <button class="btn" :disabled="!isValid">Set Motto</button>
                        </div>
                    </form>
                HTML;

                echo "</form>";
                echo "</td>";
                echo "</tr>";
            }
            if ($permissions >= Permissions::Unregistered) {
                echo "<tr>";
                echo "<td>Allow Comments on my User Wall</td>";
                echo "<td>";
                $checkedStr = ($userWallActive == 1) ? "checked" : "";
                echo "<input class='mr-2' type='checkbox' name='active' value='1' id='userwallactive' $checkedStr>";
                echo "<button class='btn' onclick='handleSetAllowComments()'>Save</button>";
                echo "</td>";
                echo "</tr>";

                echo "<tr>";
                echo "<td>Remove all comments from my User Wall</td>";
                echo "<td>";
                ?>
                <button class="btn btn-danger" onclick="handleDeleteAllUserComments()">Delete All Comments</button>
                <?php
                echo "</td>";
                echo "</tr>";
            }
            echo "</tbody></table>";
            ?>
        </div>

        <div class='component'>
            <h3>Notifications</h3>
            <table class='table-highlight'>
                <colgroup>
                    <col style='width: 300px'>
                    <col style='width: 100px'>
                </colgroup>
                <tbody>
                <tr class='do-not-highlight'>
                    <th>Event</th>
                    <th>Email Me</th>
                    <th>Site Msg</th>
                </tr>
                <tr>
                    <td>Comments on my activity</td>
                    <td><?php RenderUserPref($websitePrefs, UserPreference::EmailOn_ActivityComment, true) ?></td>
                    <td><?php RenderUserPref($websitePrefs, UserPreference::SiteMsgOn_ActivityComment, true) ?></td>
                </tr>
                <tr>
                    <td>Comments on an achievement I created</td>
                    <td><?php RenderUserPref($websitePrefs, UserPreference::EmailOn_AchievementComment, true) ?></td>
                    <td><?php RenderUserPref($websitePrefs, UserPreference::SiteMsgOn_AchievementComment, true) ?></td>
                </tr>
                <tr>
                    <td>Comments on my user wall</td>
                    <td><?php RenderUserPref($websitePrefs, UserPreference::EmailOn_UserWallComment, true) ?></td>
                    <td><?php RenderUserPref($websitePrefs, UserPreference::SiteMsgOn_UserWallComment, true) ?></td>
                </tr>
                <tr>
                    <td>Comments on a forum topic I'm involved in</td>
                    <td><?php RenderUserPref($websitePrefs, UserPreference::EmailOn_ForumReply, true) ?></td>
                    <td><?php RenderUserPref($websitePrefs, UserPreference::SiteMsgOn_ForumReply, true) ?></td>
                </tr>
                <tr>
                    <td>Someone follows me:</td>
                    <td><?php RenderUserPref($websitePrefs, UserPreference::EmailOn_Followed, true) ?></td>
                    <td><?php RenderUserPref($websitePrefs, UserPreference::SiteMsgOn_Followed, true) ?></td>
                </tr>
                <tr>
                    <td>Private message</td>
                    <td><?php RenderUserPref($websitePrefs, UserPreference::EmailOn_PrivateMessage, true) ?></td>
                    <td><?php RenderUserPref($websitePrefs, UserPreference::SiteMsgOn_PrivateMessage, true, "disabled checked") ?></td>
                </tr>
                <tr>
                    <td>Weekly RA Newsletter</td>
                    <td><?php RenderUserPref($websitePrefs, UserPreference::EmailOn_Newsletter, true) ?></td>
                    <td><?php RenderUserPref($websitePrefs, UserPreference::SiteMsgOn_Newsletter, true, "disabled") ?></td>
                </tr>
                </tbody>
            </table>
            <span id="loadingicon-1" class="transition-all duration-300 opacity-0 float-right pt-2" aria-hidden="true">
                <x-fas-spinner class="loadingicon-spinner h-5 w-5" />
                <x-fas-check class="loadingicon-done text-green-500 h-5 w-5" />
            </span>
        </div>

        <div class='component'>
            <h3>Settings</h3>
            <table class='table-highlight'>
                <tr class='do-not-highlight'>
                    <th>Setting</th>
                    <th>Enabled</th>
                </tr>
                <tr>
                    <td>
                        Suppress mature content warnings
                        <td><?php RenderUserPref($websitePrefs, UserPreference::Site_SuppressMatureContentWarning, true, $state = null, $targetLoadingIcon = 2) ?></td>
                    </td>
                </tr>
                <tr>
                    <td>
                        Show absolute dates on forum posts
                        <td><?php RenderUserPref($websitePrefs, UserPreference::Forum_ShowAbsoluteDates, true, $state = null, $targetLoadingIcon = 2) ?></td>
                    </td>
                </tr>
                <tr>
                    <td>
                        Hide missable achievement indicators
                        <td><?php RenderUserPref($websitePrefs, UserPreference::Game_HideMissableIndicators, true, $state = null, $targetLoadingIcon = 2) ?></td>
                    </td>
                </tr>
                <tr>
                    <td>
                        Only people I follow can message me or post on my wall
                        <td><?php RenderUserPref($websitePrefs, UserPreference::User_OnlyContactFromFollowing, true, $state = null, $targetLoadingIcon = 2) ?></td>
                    </td>
                </tr>
            </table>
            <span id="loadingicon-2" class="transition-all duration-300 opacity-0 float-right pt-2" aria-hidden="true">
                <x-fas-spinner class="loadingicon-spinner h-5 w-5" />
                <x-fas-check class="loadingicon-done text-green-500 h-5 w-5" />
            </span>
        </div>
        <?php
        if ($permissions >= Permissions::Registered) {
            echo "<div class='component'>";
            echo "<h3>Keys</h3>";
            echo "<table class='table-highlight'><colgroup><col style='width: 200px'></colgroup><tbody>";

            echo "<tr>";
            echo "<td class='align-top'>Web API Key</td>";
            echo "<td>";
            ?>
            <button x-init="{}" @click="copyToClipboard('{{ $apiKey }}')" class="btn flex items-center gap-x-2 mb-2" title="Copy your web API key to the clipboard" aria-label="Copy your web API key to the clipboard">
                <span class="font-mono">{{ $apiKey }}</span>
                <x-fas-copy />
            </button>
            <div class="mb-2">
                <p>
                    This is your <span class="italic">personal</span> Web API Key. Handle it with care.
                </p>
                <p>
                    API documentation can be found <a href="https://api-docs.retroachievements.org" target="_blank" rel="noreferrer">here</a>.
                </p>
            </div>
        <?php
            echo "<button class='btn btn-danger' onclick='handleResetWebApiKeyClick()'>Reset Web API Key</button>";
            echo "</td>";
            echo "</tr>";

            echo "<tr>";
            echo "<td class='align-top'>Connect Key</td>";
            echo "<td>";
            echo "<p class='mb-1'>The Connect Key is used in emulators to keep you logged in.<br>";
            echo "Resetting the key will log you out of all emulators.</p>";
            echo "<button class='btn btn-danger' onclick='handleResetConnectApiKeyClick()'>Reset Connect Key</button>";
            echo "</form>";
            echo "</td>";
            echo "</tr>";
            echo "</tbody></table>";
            echo "</div>";
        }
        ?>
        <div class='component'>
            <h3>Change Password</h3>
            <form
                x-data="{ currentPassword: '', newPassword: '', confirmPassword: '' }"
                @submit.prevent="handleChangePasswordSubmit({ currentPassword, newPassword, confirmPassword })"
            >
                <table class='table-highlight'>
                    <colgroup>
                        <col style='width: 200px'>
                    </colgroup>
                    <tbody>
                    <tr>
                        <td><label for="password_current"></label>Current Password</td>
                        <td><input type="password" name="password_current" id="password_current" x-model="currentPassword"></td>
                    </tr>
                    <tr>
                        <td><label for="password"></label>New Password</td>
                        <td><input type="password" name="password" id="password" x-model="newPassword"></td>
                    </tr>
                    <tr>
                        <td><label for="password_confirmation"></label>Confirm Password</td>
                        <td><input type="password" name="password_confirmation" id="password_confirmation" x-model="confirmPassword"></td>
                    </tr>
                    <tr class='do-not-highlight'>
                        <td></td>
                        <td>
                            <button class="btn">Change Password</button>
                        </td>
                    </tr>
                    </tbody>
                </table>
            </form>
        </div>
        <div class='component'>
            <h3>Change Email Address</h3>
            <form
                x-data="{ newEmail: '', confirmEmail: '' }"
                name='updateEmail'
                @submit.prevent="handleChangeEmailSubmit({ newEmail, confirmEmail })"
            >
                <table class='table-highlight'>
                    <colgroup>
                        <col style='width: 200px'>
                    </colgroup>
                    <tbody>
                    <tr>
                        <td><label for="email_current">Current Email Address</label></td>
                        <td>
                            <input type="email" name="email_current" id="email_current" disabled value="<?= $emailAddr ?>">
                        </td>
                    </tr>
                    <tr>
                        <td><label for="email">New Email Address</label></td>
                        <td>
                            <input type="email" name="email" id="email" x-model="newEmail">
                        </td>
                    </tr>
                    <tr>
                        <td><label for="email_confirmation">Confirm Email Address</label></td>
                        <td>
                            <input type="email" name="email_confirmation" id="email_confirmation" x-model="confirmEmail">
                        </td>
                    </tr>
                    <tr class='do-not-highlight'>
                        <td></td>
                        <td>
                            <button class="btn">Change Email Address</button>
                        </td>
                    </tr>
                    </tbody>
                </table>
            </form>
        </div>
        <div class='component'>
            <h3>Reset Game Progress</h3>
            <table class='table-highlight'>
                <colgroup>
                    <col style='width: 200px'>
                </colgroup>
                <tbody>
                    <tr>
                        <td>Game</td>
                        <td>
                            <select style='width: 400px' id='resetgameselector' onchange="ResetFetchAwarded()">
                                <option value=''>--</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td>Achievement</td>
                        <td>
                            <select style='width: 400px' id='resetachievementscontainer'>
                            <!-- Filled by JS -->
                            </select>
                        </td>
                    </tr>
                    <tr class='do-not-highlight'>
                        <td></td>
                        <td>
                            <button class='btn btn-danger' type='button' onclick="ResetProgressForSelection()">Reset Progress for Selection</button>
                            <span id="loadingiconreset" class="transition-all duration-300 opacity-0 float-right" aria-hidden="true">
                                <x-fas-spinner class="loadingicon-spinner h-5 w-5" />
                                <x-fas-check class="loadingicon-done text-green-500 h-5 w-5" />
                            </span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <script>
            var $loadingIcon = $('#loadingiconreset');
            var gameSelect = document.getElementById('resetgameselector');
            var achievementSelect = document.getElementById('resetachievementscontainer');

            function GetAllResettableGamesList() {
                // Disable achievement select and clear game select
                achievementSelect.disabled = true;
                gameSelect.replaceChildren();

                // Show loading icon
                ShowLoadingIcon('loadingiconreset');

                // Make API call to get game list
                $.get('{{ route('player.games.resettable') }}').done(({ results }) => {

                    // Create a document fragment to hold the options
                    const fragment = new DocumentFragment();

                    // Create a default option
                    const option = document.createElement('option');
                    option.value = '';
                    option.textContent = '--';
                    fragment.appendChild(option);

                    // Create an option for each game and append it to the fragment
                    for (const game of results) {
                        const option = document.createElement('option');
                        option.value = game.id;
                        option.textContent = `${game.title} (${game.consoleName}) (${game.numAwarded} / ${game.numPossible} won)`;
                        fragment.appendChild(option);
                    }

                    // Replace the game select's contents with the fragment and re-enable it
                    gameSelect.replaceChildren(fragment);
                    gameSelect.disabled = false;

                    // Hide the loading icon after a delay
                    ShowDoneIcon('loadingiconreset');
                });
            }

            function ResetFetchAwarded() {
                achievementSelect.replaceChildren();
                achievementSelect.setAttribute('disabled', 'disabled');
                var gameID = parseInt(gameSelect.options[gameSelect.selectedIndex].value);
                if (!gameID) {
                    return;
                }
                gameSelect.setAttribute('disabled', 'disabled');
                achievementSelect.innerHTML += '<option value=\'\'>--</option>';
                ShowLoadingIcon('loadingiconreset');

                $.get(`/game/${gameID}/achievements/resettable`)
                    .done(function ({ results }) {
                        achievementSelect.replaceChildren();
                        achievementSelect.innerHTML += '<option value=\'all\' >All achievements for this game</option>';
                        results.forEach(function (achievement) {
                            var achTitle = achievement.title;
                            var achID = achievement.id;
                            achievementSelect.innerHTML += '<option value=\'' + achID + '\'>' + achTitle + (achievement.isHardcore ? ' (Hardcore)' : '') + '</option>';
                        });
                        gameSelect.removeAttribute('disabled');
                        achievementSelect.removeAttribute('disabled');
                        ShowDoneIcon('loadingiconreset');
                    });
            }

            function ResetProgressForSelection() {
                var achOption = achievementSelect.options[achievementSelect.selectedIndex];
                var gameOption = gameSelect.options[gameSelect.selectedIndex];
                var achName = achOption.text;
                var achID = achOption.value;
                var isHardcore = 0;
                if (achID[0] === 'h') {
                    achID = achID.substr(1);
                    isHardcore = 1;
                }
                if (achID === 'all') {
                    var gameName = gameOption.text;
                    var gameId = gameOption.value;
                    gameName = gameName.substr(0, gameName.lastIndexOf('(') - 1);

                    if (gameId > 0 && confirm('Reset all achievements for "' + gameName + '"?')) {
                        ShowLoadingIcon('loadingiconreset');

                        $.ajax({
                            url: `/user/game/${gameId}`,
                            type: 'DELETE',
                            success: () => {
                                ShowDoneIcon('loadingiconreset');
                                achievementSelect.replaceChildren();
                                GetAllResettableGamesList();
                            }
                        });
                    }
                } else if (achID > 0 && confirm('Reset achievement "' + achName + '"?')) {
                    ShowLoadingIcon('loadingiconreset');

                    $.ajax({
                        url: `/user/achievement/${achID}`,
                        type: 'DELETE',
                        success: () => {
                            ShowDoneIcon('loadingiconreset');
                            if ($('#resetachievementscontainer').children('option').length > 2) {
                                // Just reset ach. list
                                ResetFetchAwarded();
                            } else {
                                // all achievements gone: fetch new list!
                                achievementSelect.replaceChildren();
                                GetAllResettableGamesList();
                            }
                        }
                    });
                }
            }

            GetAllResettableGamesList();
        </script>
        <div class='component'>
            <h3>Delete Account</h3>
            <p class='embedded mb-3'>
                After requesting account deletion you may cancel your request within 14 days.<br>
                Your account's username will NOT be available after the deletion.<br>
                Your account's personal data will be cleared from the database permanently.<br>
                Content you wrote in forums, comments, etc. will NOT be removed.
            </p>
            <?php if ($userDetails['DeleteRequested']): ?>
                <p class='embedded mb-3'>
                    You requested to have your account deleted on <?= $userDetails['DeleteRequested'] ?> (UTC).<br>
                    Your account will be permanently deleted on <?= getDeleteDate($userDetails['DeleteRequested']) ?>.
                </p>
                <button class='btn' onclick='handleCancelRequestAccountDeletion()'>Cancel account deletion request</button>
            <?php else: ?>
                <button class='btn btn-danger' onclick='handleRequestAccountDeletion()'>Request account deletion</button>
            <?php endif ?>
        </div>
    </div>
@if ($permissions >= Permissions::Registered)
    <x-slot name="sidebar">
        <div class='component'>
            <h3>Site Awards</h3>
            <div style="margin-bottom: 10px">
                You can manually set the display order for your earned awards.
            </div>
            <a class="btn btn-link" href="reorderSiteAwards.php">Reorder Site Awards</a>
        </div>

        @if (!$userModel->isMuted())
            <div class='component'>
                <h3>Avatar</h3>
                @if ($userModel->can('updateAvatar', [User::class]))
                    <div style="margin-bottom: 10px">
                        New image should be less than 1MB, png/jpeg/gif supported.
                    </div>
                    <div style="margin-bottom: 10px">
                        <input type="file" name="file" id="uploadimagefile" onchange="return UploadNewAvatar();">
                        <span id="loadingiconavatar" class="transition-all duration-300 opacity-0 float-right pt-1" aria-hidden="true">
                            <x-fas-spinner class="loadingicon-spinner h-5 w-5" />
                            <x-fas-check class="loadingicon-done text-green-500 h-5 w-5" />
                        </span>
                    </div>
                    <div style="margin-bottom: 10px">
                        After uploading, press Ctrl + F5. This refreshes your browser cache making the image visible.
                    </div>
                    <div style="margin-bottom: 10px">
                        Reset your avatar to default by removing your current one:
                    </div>
                    <button class="btn btn-danger" onclick='handleRemoveAvatar()'>Remove Avatar</button>
                @else
                    <div style="margin-bottom: 10px">
                        To upload an avatar, earn 250 points in either mode or wait until your account is at least 14 days old.
                    </div>
                @endif
            </div>
        @endif
    </x-slot>
@endif
</x-app-layout>

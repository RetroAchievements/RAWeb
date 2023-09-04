<?php

use App\Site\Enums\Permissions;
use App\Site\Enums\UserPreference;

if (!authenticateFromCookie($user, $permissions, $userDetails)) {
    abort(401);
}

// view()->share('wide', true);

// cookie only returns the most common account details. go get the rest
getAccountDetails($user, $userDetails);
$points = (int) $userDetails['RAPoints'];
$websitePrefs = (int) $userDetails['websitePrefs'];
$emailAddr = $userDetails['EmailAddress'];
$permissions = (int) $userDetails['Permissions'];
$contribCount = (int) $userDetails['ContribCount'];
$contribYield = (int) $userDetails['ContribYield'];
$userWallActive = $userDetails['UserWallActive'];
$apiKey = $userDetails['APIKey'];
$userMotto = htmlspecialchars($userDetails['Motto']);

RenderContentStart("My Settings");

function RenderUserPref(
    int $websitePrefs,
    int $userPref,
    bool $setIfTrue,
    ?string $state = null,
    int $targetLoadingIcon = 1,
): void {
    echo "<input id='UserPreference$userPref' type='checkbox' ";
    echo "onchange='DoChangeUserPrefs($targetLoadingIcon); return false;' value='1'";

    if ($state) {
        echo " $state";
    } elseif (BitSet($websitePrefs, $userPref) === $setIfTrue) {
        echo " checked";
    }

    echo " />";
}
?>
<script>
/**
 * @param {number} targetLoadingIcon - There are multiple loading icons on the page, which one will update based on this prefs change?
 */
function DoChangeUserPrefs(targetLoadingIcon = 1) {
    var newUserPrefs = 0;
    for (i = 0; i <= 15; ++i) {
        var checkbox = document.getElementById('UserPreference' + i);
        if (checkbox != null && checkbox.checked) {
            newUserPrefs += (1 << i);
        }
    }

    const loadingIconId = `#loadingicon-${targetLoadingIcon}`;
    $(loadingIconId).attr('src', '<?= asset('assets/images/icon/loading.gif') ?>').fadeTo(100, 1.0);
    $.post('/request/user/update-preferences.php', {
        preferences: newUserPrefs
    })
        .done(function () {
            $(loadingIconId).attr('src', '<?= asset('assets/images/icon/tick.png') ?>').delay(750).fadeTo('slow', 0.0);
        });
}

function UploadNewAvatar() {
    // New file
    var photo = document.getElementById('uploadimagefile');
    var file = photo.files[0];
    var reader = new FileReader();
    reader.onload = function () {
        $('#loadingiconavatar').fadeTo(100, 1.0);
        $.post('/request/user/update-avatar.php', { imageData: reader.result },
            function (data) {
                $('#loadingiconavatar').fadeTo(100, 0.0);

                var result = $.parseJSON(data);
                var d = new Date();
                $('.userpic').attr('src', '<?= media_asset('/UserPic/' . $user . '.png')  ?>' + '?' + d.getTime());
            });
    };
    reader.readAsDataURL(file);
    return false;
}

function confirmEmailChange(event) {
    <?php if ($permissions >= Permissions::Developer): ?>
    return confirm('Changing your email address will revoke your privileges and you will need to have them restored by staff.');
    <?php else: ?>
    return true;
    <?php endif ?>
}
</script>
<article>
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
                echo "<form class='flex gap-2 mb-1' method='post' action='/request/user/update-motto.php'>";
                echo csrf_field();

                echo <<<HTML
                    <div x-data="{ isValid: true }" class="flex gap-x-2">
                        <div class="grid gap-y-1">
                            <input
                                id="motto"
                                name="motto"
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
                HTML;

                echo "</form>";
                echo "</td>";
                echo "</tr>";
            }
            if ($permissions >= Permissions::Unregistered) {
                echo "<tr>";
                echo "<td>Allow Comments on my User Wall</td>";
                echo "<td>";
                echo "<form method='post' action='/request/user-comment/toggle.php'>";
                echo csrf_field();
                $checkedStr = ($userWallActive == 1) ? "checked" : "";
                echo "<input class='mr-2' type='checkbox' name='active' value='1' id='userwallactive' $checkedStr>";
                echo "<button class='btn'>Save</button>";
                echo "</form>";
                echo "</td>";
                echo "</tr>";

                echo "<tr>";
                echo "<td>Remove all comments from my User Wall</td>";
                echo "<td>";
                echo "<form method='post' action='/request/user-comment/delete-all.php' onsubmit='return confirm(\"Are you sure you want to permanently delete all comment on your wall?\");'>";
                echo csrf_field();
                echo "<button class='btn btn-danger'>Delete All Comments</button>";
                echo "</form>";
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
            <img id='loadingicon-1' style='opacity: 0; float: right;' src='<?= asset('assets/images/icon/loading.gif') ?>' width='16' height='16' alt='loading icon'/>
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
            </table>
            <img id='loadingicon-2' style='opacity: 0; float: right;' src='<?= asset('assets/images/icon/loading.gif') ?>' width='16' height='16' alt='loading icon'/>
        </div>
        <?php
        if ($permissions >= Permissions::Registered) {
            echo "<div class='component'>";
            echo "<h3>Keys</h3>";
            echo "<table class='table-highlight'><colgroup><col style='width: 200px'></colgroup><tbody>";

            echo "<tr>";
            echo "<td class='align-top'>Web API Key</td>";
            echo "<td>";
            echo "<input class='mb-1' size='60' readonly value='$apiKey'>";
            echo "<div class='mb-2'>This is your <i>personal</i> Web API Key. Handle it with care.</div>";
            echo "<form method='post' action='/request/auth/reset-api-key.php' onsubmit='return confirm(\"Are you sure you want to reset your web api key?\");'>";
            echo csrf_field();
            $checkedStr = ($userWallActive == 1) ? "checked" : "";
            echo "<button class='btn btn-danger'>Reset Web API key</button>";
            echo "</form>";
            echo "</td>";
            echo "</tr>";

            echo "<tr>";
            echo "<td class='align-top'>Connect Key</td>";
            echo "<td>";
            echo "<p class='mb-1'>The Connect Key is used in emulators to keep you logged in.<br>";
            echo "Resetting the key will log you out of all emulators.</p>";
            echo "<form method='post' action='/request/auth/reset-connect-key.php' onsubmit='return confirm(\"Are you sure you want to reset your connect key?\");'>";
            echo csrf_field();
            $checkedStr = ($userWallActive == 1) ? "checked" : "";
            echo "<button class='btn btn-danger'>Reset Connect Key</button>";
            echo "</form>";
            echo "</td>";
            echo "</tr>";
            echo "</tbody></table>";
            echo "</div>";
        }
        ?>
        <div class='component'>
            <h3>Change Password</h3>
            <form method='post' action='/request/auth/update-password.php'>
                <?= csrf_field() ?>
                <table class='table-highlight'>
                    <colgroup>
                        <col style='width: 200px'>
                    </colgroup>
                    <tbody>
                    <tr>
                        <td><label for="password_current"></label>Current Password</td>
                        <td><input type="password" name="password_current" id="password_current"></td>
                    </tr>
                    <tr>
                        <td><label for="password"></label>New Password</td>
                        <td><input type="password" name="password" id="password"></td>
                    </tr>
                    <tr>
                        <td><label for="password_confirmation"></label>Confirm Password</td>
                        <td><input type="password" name="password_confirmation" id="password_confirmation"></td>
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
            <form name='updateEmail' method='post' action='/request/user/update-email.php' onsubmit='return confirmEmailChange()'>
                <?= csrf_field() ?>
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
                            <input type="email" name="email" id="email">
                        </td>
                    </tr>
                    <tr>
                        <td><label for="email_confirmation">Confirm Email Address</label></td>
                        <td>
                            <input type="email" name="email_confirmation" id="email_confirmation">
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
            <?php
            echo "<table class='table-highlight'><colgroup><col style='width: 200px'></colgroup><tbody>";
            echo "<tr><td>Game</td>";
            echo "<td><select style='width: 400px' id='resetgameselector' onchange=\"ResetFetchAwarded()\">";
            echo "<option value=''>--</option>";
            echo "</select></td></tr>";

            echo "<tr><td>Achievement</td>";
            echo "<td><select style='width: 400px' id='resetachievementscontainer'>";
            // Filled by JS
            echo "</select></td></tr>";

            echo "<tr class='do-not-highlight'><td></td><td><button class='btn btn-danger' type='button' onclick=\"ResetProgressForSelection()\">Reset Progress for Selection</button>";
            echo "<img id='loadingiconreset' style='opacity: 0; float: right;' src='" . asset('assets/images/icon/loading.gif') . "' width='16' height='16' alt='loading icon' />";
            echo "</td></tr></tbody></table>";
            ?>
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
                $loadingIcon.attr('src', '<?= asset('assets/images/icon/loading.gif') ?>').fadeTo(100, 1.0);

                // Make API call to get game list
                $.post('/request/user/list-games.php').done(data => {
                    // Create a document fragment to hold the options
                    const fragment = new DocumentFragment();

                    // Create a default option
                    const option = document.createElement('option');
                    option.value = '';
                    option.textContent = '--';
                    fragment.appendChild(option);

                    // Create an option for each game and append it to the fragment
                    for (const game of data) {
                        const option = document.createElement('option');
                        option.value = game.ID;
                        option.textContent = `${game.GameTitle} (${game.ConsoleName}) (${game.NumAwarded} / ${game.NumPossible} won)`;
                        fragment.appendChild(option);
                    }

                    // Replace the game select's contents with the fragment and re-enable it
                    gameSelect.replaceChildren(fragment);
                    gameSelect.disabled = false;

                    // Hide the loading icon after a delay
                    $loadingIcon.delay(750).fadeTo('slow', 0.0);
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
                $loadingIcon.attr('src', '<?= asset('assets/images/icon/loading.gif') ?>').fadeTo(100, 1.0);
                $.post('/request/user/list-unlocks.php', { game: gameID })
                    .done(function (data) {
                        achievementSelect.replaceChildren();
                        achievementSelect.innerHTML += '<option value=\'all\' >All achievements for this game</option>';
                        data.forEach(function (achievement) {
                            var achTitle = achievement.Title;
                            var achID = achievement.ID;
                            achievementSelect.innerHTML += '<option value=\'' + achID + '\'>' + achTitle + (achievement.HardcoreMode ? ' (Hardcore)' : '') + '</option>';
                        });
                        gameSelect.removeAttribute('disabled');
                        achievementSelect.removeAttribute('disabled');
                        $loadingIcon.delay(750).fadeTo('slow', 0.0);
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
                        $loadingIcon.attr('src', '<?= asset('assets/images/icon/loading.gif') ?>').fadeTo(100, 1.0);
                        $.post('/request/user/reset-achievements.php', { game: gameId })
                            .done(function () {
                                $loadingIcon.attr('src', '<?= asset('assets/images/icon/tick.png') ?>').delay(750).fadeTo('slow', 0.0);
                                achievementSelect.replaceChildren();
                                GetAllResettableGamesList();
                            });
                    }
                } else if (achID > 0 && confirm('Reset achievement "' + achName + '"?')) {
                    $loadingIcon.attr('src', '<?= asset('assets/images/icon/loading.gif') ?>').fadeTo(100, 1.0);
                    $.post('/request/user/reset-achievements.php', {
                        achievement: achID,
                    })
                        .done(function () {
                            $loadingIcon.attr('src', '<?= asset('assets/images/icon/tick.png') ?>').delay(750).fadeTo('slow', 0.0);
                            if ($('#resetachievementscontainer').children('option').length > 2) {
                                // Just reset ach. list
                                ResetFetchAwarded();
                            } else {
                                // all achievements gone: fetch new list!
                                achievementSelect.replaceChildren();
                                GetAllResettableGamesList();
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
                <form method="post" action="/request/auth/delete-account-cancel.php" onsubmit="return confirm('Are you sure you want to cancel your account deletion request?');">
                    <?= csrf_field() ?>
                    <button class='btn'>Cancel account deletion request</button>
                </form>
            <?php else: ?>
                <form method="post" action="/request/auth/delete-account.php" onsubmit="return confirm('Are you sure you want to request account deletion?');">
                    <?= csrf_field() ?>
                    <button class='btn btn-danger'>Request account deletion</button>
                </form>
            <?php endif ?>
        </div>
    </div>
</article>
<?php if ($permissions >= Permissions::Registered): ?>
    <?php view()->share('sidebar', true) ?>
    <aside>
        <div class='component'>
            <h3>Site Awards</h3>
            <div style="margin-bottom: 10px">
                You can manually set the display order for your earned awards.
            </div>
            <a class="btn btn-link" href="reorderSiteAwards.php">Reorder Site Awards</a>
        </div>
        <div class='component'>
            <h3>Avatar</h3>
            <div style="margin-bottom: 10px">
                New image should be less than 1MB, png/jpeg/gif supported.
            </div>
            <div style="margin-bottom: 10px">
                <input type="file" name="file" id="uploadimagefile" onchange="return UploadNewAvatar();">
                <img id="loadingiconavatar" style="opacity: 0; float: right;"
                     src="<?= asset('assets/images/icon/loading.gif') ?>"
                     width="16" height="16" alt="loading">
            </div>
            <div style="margin-bottom: 10px">
                After uploading, press Ctrl + F5. This refreshes your browser cache making the image visible.
            </div>
            <div style="margin-bottom: 10px">
                Reset your avatar to default by removing your current one:
            </div>
            <form method="post" action="/request/user/remove-avatar.php" onsubmit="return confirm('Are you sure you want to permanently delete this avatar?')">
                <?= csrf_field() ?>
                <button class="btn btn-danger">Remove Avatar</button>
            </form>
        </div>
        <div class='component'>
            <h3>Request Score Recalculation</h3>
            <form method="post" action="/request/user/recalculate-score.php">
                <?= csrf_field() ?>
                <input type="hidden" name="user" value="<?= $user ?>">
                If you feel your score is inaccurate due to point values varying during achievement development, you can request a recalculation by using the button below.<br><br>
                <button class="btn">Recalculate My Score</button>
            </form>
        </div>
    </aside>
<?php endif ?>
<?php RenderContentEnd(); ?>

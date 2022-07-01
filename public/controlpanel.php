<?php

use RA\Permissions;
use RA\UserPreference;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../lib/bootstrap.php';

if (!authenticateFromCookie($user, $permissions, $userDetails)) {
    // Immediate redirect if we cannot validate cookie!
    header("Location: " . getenv('APP_URL') . "?e=notloggedin");
    exit;
}

// cookie only returns the most common account details. go get the rest
getAccountDetails($user, $userDetails);
$points = $userDetails['RAPoints'];
$websitePrefs = $userDetails['websitePrefs'];
$emailAddr = $userDetails['EmailAddress'];
$permissions = $userDetails['Permissions'];
$contribCount = $userDetails['ContribCount'];
$contribYield = $userDetails['ContribYield'];
$userWallActive = $userDetails['UserWallActive'];
$apiKey = $userDetails['APIKey'];
$userMotto = htmlspecialchars($userDetails['Motto']);

$errorCode = requestInputSanitized('e');

RenderHtmlStart();
RenderHtmlHead("My Settings");

function RenderUserPref($websitePrefs, $userPref, $setIfTrue, $state = null): void
{
    echo "<input id='UserPreference$userPref' type='checkbox' ";
    echo "onchange='DoChangeUserPrefs(); return false;' value='1'";

    if ($state) {
        echo " $state";
    } elseif (BitSet($websitePrefs, $userPref) == $setIfTrue) {
        echo " checked";
    }

    echo " />";
}

?>
<body>
<script>
  function GetAllResettableGamesList() {
    $('#resetgameselector').empty();

    var posting = $.post('/request/user/list-games.php', {u: '<?= $user ?>'});
    posting.done(function (data) {
      if (data !== 'ERROR3') {
        var htmlToAdd = '<select id=\'resetgameselector\' onchange="ResetFetchAwarded()" >';
        htmlToAdd += '<option value="">--</option>';

        var gameList = JSON.parse(data);

        for (var i = 0; i < gameList.length; ++i) {
          var object = gameList[i];

          var nextID = object.ID;
          var console = htmlEntities(object.ConsoleName);
          var title = htmlEntities(object.GameTitle);
          var numAw = object.NumAwarded;
          var numPoss = object.NumPossible;

          htmlToAdd += '<option value=\'' + nextID + '\'>' + title + ' (' + console + ') (' + numAw + ' / ' + numPoss + ' won)';
        }

        htmlToAdd += '</select>';

        $('#resetgameselector').html(htmlToAdd);

        $('#loadingiconreset').attr('src', '<?= asset('Images/tick.png') ?>').delay(750).fadeTo('slow', 0.0);
      }

      ResetFetchAwarded();
    });

    $('#loadingiconreset').attr('src', '<?php echo getenv('ASSET_URL') ?>/Images/loading.gif').fadeTo(100, 1.0);
  }

  function ResetFetchAwarded() {
    var gameID = parseInt($('#resetgameselector :selected').val());
    if (gameID > 0) {
      var posting = $.post('/request/user/list-unlocks.php', {u: '<?= $user ?>', g: gameID});
      posting.done(function (data) {
        if (data.substr(0, 2) !== 'OK') {
          showStatusFailure('Error: ' + data);
        } else {
          hideStatusMessage();

          var achList = data.substr(3);
          var achData = achList.split('::');

          if (achData.length > 0 && achData[0].length > 0) {
            $('#resetachievementscontainer').append('<option value=\'9999999\' >All achievements for this game</option>');
          }

          for (var index = 0; index < achData.length; ++index) {
            var nextData = achData[index];
            var dataChunks = nextData.split('_:_');
            if (dataChunks.length < 2) {
              continue;
            }
            var achTitle = htmlEntities(dataChunks[0]);
            var achID = htmlEntities(dataChunks[1]);
            if (achID[0] == 'h') {
              // Hardcore:
              achTitle = achTitle + ' (Hardcore)';
              $('#resetachievementscontainer').append('<option value=\'' + achID + '\'>' + achTitle + '</option>');
            } else {
              // Casual:
              $('#resetachievementscontainer').append('<option value=\'' + achID + '\'>' + achTitle + '</option>');
            }
          }
        }
      });
      $('#resetachievementscontainer').empty();
      showStatusMessage('Updating...');
    }
  }

  function ResetProgressForSelection() {
    var achName = $('#resetachievementscontainer :selected').text();
    var achID = $('#resetachievementscontainer :selected').val();

    var isHardcore = 0;
    if (achID[0] == 'h') {
      achID = achID.substr(1);
      isHardcore = 1;
    }

    if (achID == 9999999) {
      var gameName = $('#resetgameselector :selected').text();
      var gameId = $('#resetgameselector :selected').val();
      gameName = gameName.substr(0, gameName.lastIndexOf('(') - 1);

      // Prompt user for confirmation if attempting to remove all achievement for a single game
      if (gameId > 0 && confirm('Reset all achievements for ' + gameName + '?')) {
        // 'All Achievements' selected: reset this game entirely!
        var gameID = $('#resetgameselector :selected').val();
        var posting = $.post('/request/user/reset-achievements.php', {u: '<?= $user ?>', g: gameID});
        posting.done(onResetComplete);
        $('#loadingiconreset').attr('src', '<?= asset('Images/loading.gif') ?>').fadeTo(100, 1.0);
      }
    } else if (achID > 0 && confirm('Reset achievement ' + achName + '?')) {
      // Particular achievement selected: reset just this achievement
      var posting = $.post('/request/user/reset-achievements.php', {u: '<?= $user ?>', a: achID, h: isHardcore});
      posting.done(onResetComplete);
      $('#loadingiconreset').attr('src', '<?= asset('Images/loading.gif') ?>').fadeTo(100, 1.0);
    }
  }

  function onResetComplete(data) {
    if (data.substr(0, 2) !== 'OK') {
      alert(data);
      //showStatusFailure('Error: ' + data);
      $('#loadingiconreset').attr('src', '<?= asset('Images/tick.png') ?>').delay(750).fadeTo('slow', 0.0);
      return;
    }
    $('#loadingiconreset').attr('src', '<?= asset('Images/tick.png') ?>').delay(750).fadeTo('slow', 0.0);
    if ($('#resetachievementscontainer').children('option').length > 2)
      ResetFetchAwarded(); // Just reset ach. list
    else
      GetAllResettableGamesList(); // last ach reset: fetch new list!
    return false;
  }

  function DoChangeUserPrefs() {
    var newUserPrefs = 0;
    for (i = 0; i < 7; ++i) { // 0-6 are set if checked
      var checkbox = document.getElementById('UserPreference' + i);
      if (checkbox != null && checkbox.checked)
        newUserPrefs += (1 << i);
    }

    for (i = 8; i < 15; ++i) { // 8-14 are set if checked
      var checkbox = document.getElementById('UserPreference' + i);
      if (checkbox != null && checkbox.checked)
        newUserPrefs += (1 << i);
    }

    // 7 is set if unchecked
    var checkbox = document.getElementById('UserPreference7');
    if (checkbox != null && !checkbox.checked)
      newUserPrefs += (1 << 7);

    $('#loadingicon').attr('src', '<?= asset('Images/loading.gif') ?>').fadeTo(100, 1.0);
    var posting = $.post('/request/user/update-notification.php', {u: '<?= $user ?>', p: newUserPrefs});
    posting.done(function () {
      $('#loadingicon').attr('src', '<?= asset('Images/tick.png') ?>').delay(750).fadeTo('slow', 0.0);
    });
  }

  function UploadNewAvatar() {
    // New file
    var photo = document.getElementById('uploadimagefile');
    var file = photo.files[0];
    var reader = new FileReader();
    reader.onload = function () {
      $('#loadingiconavatar').fadeTo(100, 1.0);
      $.post('/request/user/update-avatar.php', { i: reader.result },
        function (data) {
          $('#loadingiconavatar').fadeTo(100, 0.0);

          var result = $.parseJSON(data);
          if (result.Success) {
            var d = new Date();
            $('.userpic').attr('src', '/UserPic/<?= $user ?>' + '.png?' + d.getTime());
          } else {
            showStatusFailure('Upload failed: ' + result.Error);
          }
        });
    };
    reader.readAsDataURL(file);
    return false;
  }

  function validateEmail() {
    var oldEmail = document.forms['updateEmail']['o'].value;
    var newEmail = document.forms['updateEmail']['e'].value;
    var verifyEmail = document.forms['updateEmail']['f'].value;
    if (newEmail != verifyEmail) {
      showStatusFailure("New email addresses are not identical");
      return false;
    }
    if (newEmail == oldEmail) {
      showStatusFailure("New email address is same as old email address");
      return false;
    }
    <?php if ($permissions >= Permissions::Developer): ?>
    return confirm("Are you sure that you want to do this?\n\nChanging your email address will revoke your privileges and you will need to have them restored by staff.");
    <?php else: ?>
    return true;
    <?php endif ?>
  }

  GetAllResettableGamesList();
</script>
<?php RenderHeader($userDetails); ?>
<div id="mainpage">
    <div id="leftcontainer">
        <div class='detaillist'>
        <?php
            RenderStatusWidget(
                errorMessage: match ($errorCode) {
                    'baddata' => 'Errors changing your password. Please check and try again!',
                    'generalerror' => 'Errors changing your password. Please check and try again!',
                    'badnewpass' => 'Errors changing your password, passwords too short!',
                    'passinequal' => 'Errors changing your password, new passwords were not identical!',
                    'badpassold' => 'Errors changing your password, old password was incorrect!',
                    'e_baddata' => 'Errors changing your email address. Please check and try again!',
                    'e_generalerror' => 'Errors changing your email address. Please check and try again!',
                    'e_badnewemail' => 'Errors changing your email address, the new email doesn\'t appear to be valid!',
                    'e_notmatch' => 'Errors changing your email address, new emails were not identical!',
                    'e_badcredentials' => 'Errors changing your email address, session invalid. Please log out and back in, and try again!',
                    default => null,
                },
                successMessage: match ($errorCode) {
                    'changepassok' => 'Password changed OK!',
                    'e_changeok' => 'Email address changed OK!',
                    default => null,
                }
            );

            if ($permissions == Permissions::Unregistered) {
                echo "<div id='warning'>Warning: Email address not confirmed. Please check your inbox or spam folders, or click <a href='/request/auth/send-verification-email.php?u=$user'>here</a> to resend your verification email!</div>";
            }
        ?>
        <div class='component'>
            <h2>User Details</h2>
            <div class="embedded d-flex justify-content-between">
                <div>
                    <div><strong><a href="/user/<?= $user ?>"><?= $user ?></a></strong> (<?= $points ?> points)</div>
                    <div>Account: (<?= $permissions ?>) <?= Permissions::toString($permissions) ?></div>
                    <?php if (!empty($userMotto) && mb_strlen($userMotto) > 1) : ?>
                        <span class="usermotto"><?= $userMotto ?></span>
                    <?php endif ?>
                </div>
                <img class="userpic" src="/UserPic/<?= $user ?>.png" alt="<?= $user ?> avatar" width='96' height='96' />
            </div>
            <?php
            echo "<table><tbody>";
            if ($permissions >= Permissions::Registered) {
                $userMottoString = !empty($userMotto) ? $userMotto : "";
                echo "<tr>";
                echo "<td>User Motto:</td>";
                echo "<td>";
                echo "<form method='POST' action='/request/user/update-motto.php'>";
                echo "<input class='fullwidth' name='m' value=\"$userMottoString\" maxlength='49' id='usermottoinput' placeholder='Add your motto here! (No profanity please!)' />";
                echo "<input value='Set Motto' name='submit' type='submit' size='37' />";
                echo "</form>";
                echo "</td>";
                echo "</tr>";
            }
            if ($permissions >= Permissions::Unregistered) {
                echo "<tr>";
                echo "<td>Allow Comments on my User Wall:</td>";
                echo "<td>";
                echo "<form method='POST' action='/request/user/update-wall.php'>";
                $checkedStr = ($userWallActive == 1) ? "checked" : "";
                echo "<input type='checkbox' name='v' value='1' id='userwallactive' $checkedStr/>";
                echo "<input type='hidden' name='t' value='wall'>";
                echo "<input value='Save' name='submit' type='submit' size='37' />";
                echo "</form>";
                echo "</td>";
                echo "</tr>";

                echo "<tr>";
                echo "<td>Remove all comments from my User Wall:</td>";
                echo "<td>";
                echo "<form method='POST' action='/request/user/update-wall.php' onsubmit='return confirm(\"Are you sure you want to permanently delete all comment on your wall?\");'>";
                echo "<input type='hidden' name='t' value='cleanwall'>";
                echo "<input value='Delete All Comments' name='submit' type='submit' size='37' />";
                echo "</form>";
                echo "</td>";
                echo "</tr>";
            }
            echo "</tbody></table>";
            ?>
        </div>
        <?php
        if ($permissions >= Permissions::Registered) {
            echo "<div class='component'>";
            echo "<h3>Keys</h3>";
            echo "<table><tbody>";

            echo "<tr>";
            echo "<td><a href='/APIDemo.php'>Web API Key:</a></td>";
            echo "<td>";
            echo "This is your <em>personal</em> Web API Key.<br>Handle it with care.";
            echo "<input size='60' readonly type='text' value='$apiKey' />";
            echo "<form method='POST' action='/request/auth/reset-api-key.php' onsubmit='return confirm(\"Are you sure you want to reset your web api key?\");'>";
            $checkedStr = ($userWallActive == 1) ? "checked" : "";
            echo "<input value='Reset Web API key' name='submit' type='submit' size='37' />";
            echo "</form>";
            echo "</td>";
            echo "</tr>";

            echo "<tr>";
            echo "<td>Connect Key:</td>";
            echo "<td>";
            echo "The Connect Key is used in emulators to keep you logged in.<br>";
            echo "Resetting the key will log you out of all emulators.<br>";
            echo "<form method='POST' action='/request/auth/reset-connect-key.php' onsubmit='return confirm(\"Are you sure you want to reset your connect key?\");'>";
            $checkedStr = ($userWallActive == 1) ? "checked" : "";
            echo "<input value='Reset Connect Key' name='submit' type='submit' size='37' />";
            echo "</form>";
            echo "</td>";
            echo "</tr>";

            echo "<tr><td>Twitch.tv streamkey:</td><td><input size='60' readonly type='text' value='live_46798798_5tO2CCgggTMoi5458BLKUADECNpOrq' /></td></tr>";

            echo "</tbody></table>";
            echo "</div>";
        }
        ?>
        <div class='component'>
            <h3>Notifications</h3>
            When would you like to be notified?
            <table>
                <tbody>
                <tr>
                    <th>Event</th>
                    <th>Email Me</th>
                    <th>Site Msg</th>
                </tr>
                <tr>
                    <td>If someone comments on my activity:</td>
                    <td><?php RenderUserPref($websitePrefs, UserPreference::EmailOn_ActivityComment, true) ?></td>
                    <td><?php RenderUserPref($websitePrefs, UserPreference::SiteMsgOn_ActivityComment, true) ?></td>
                </tr>
                <tr>
                    <td>If someone comments on an achievement I created:</td>
                    <td><?php RenderUserPref($websitePrefs, UserPreference::EmailOn_AchievementComment, true) ?></td>
                    <td><?php RenderUserPref($websitePrefs, UserPreference::SiteMsgOn_AchievementComment, true) ?></td>
                </tr>
                <tr>
                    <td>If someone comments on my user wall:</td>
                    <td><?php RenderUserPref($websitePrefs, UserPreference::EmailOn_UserWallComment, true) ?></td>
                    <td><?php RenderUserPref($websitePrefs, UserPreference::SiteMsgOn_UserWallComment, true) ?></td>
                </tr>
                <tr>
                    <td>If someone comments on a forum topic I'm involved in:</td>
                    <td><?php RenderUserPref($websitePrefs, UserPreference::EmailOn_ForumReply, true) ?></td>
                    <td><?php RenderUserPref($websitePrefs, UserPreference::SiteMsgOn_ForumReply, true) ?></td>
                </tr>
                <tr>
                    <td>If someone follows me:</td>
                    <td><?php RenderUserPref($websitePrefs, UserPreference::EmailOn_AddFriend, true) ?></td>
                    <td><?php RenderUserPref($websitePrefs, UserPreference::SiteMsgOn_AddFriend, true) ?></td>
                </tr>
                <tr>
                    <td>If someone sends me a private message:</td>
                    <td><?php RenderUserPref($websitePrefs, UserPreference::EmailOn_PrivateMessage, true) ?></td>
                    <td><?php RenderUserPref($websitePrefs, UserPreference::SiteMsgOn_PrivateMessage, true, "disabled checked") ?></td>
                </tr>
                <tr>
                    <td>With the weekly RA Newsletter:</td>
                    <td><?php RenderUserPref($websitePrefs, UserPreference::EmailOn_Newsletter, true) ?></td>
                    <td><?php RenderUserPref($websitePrefs, UserPreference::SiteMsgOn_Newsletter, true, "disabled") ?></td>
                </tr>
                <tr>
                    <td>When viewing a game with mature content:</td>
                    <td/>
                    <td><?php RenderUserPref($websitePrefs, UserPreference::SiteMsgOff_MatureContent, false) ?></td>
                </tr>
                </tbody>
            </table>
            <img id='loadingicon' style='opacity: 0; float: right;' src='<?= asset('Images/loading.gif') ?>' width='16' height='16' alt='loading icon'/>
        </div>
        <div class='component'>
            <h3>Change Password</h3>
            <form method='post' action='/request/auth/update-password.php'>
                <input type="hidden" name="u" value="<?= $user ?>">
                <table>
                    <tbody>
                    <tr>
                        <td class='firstrow'>Old Password:</td>
                        <td><input size='22' type='password' name="p"/></td>
                    </tr>
                    <tr>
                        <td class='firstrow'>New Password:</td>
                        <td><input size='22' type='password' name="x"/></td>
                    </tr>
                    <tr>
                        <td class='firstrow'>New Password again:</td>
                        <td><input size='22' type='password' name="y"/></td>
                    </tr>
                    <tr>
                        <td></td>
                        <td><input value="Change Password" name='submit' type='submit' size='37'></td>
                    </tr>
                    </tbody>
                </table>
            </form>
        </div>
        <div class='component'>
            <h3>Change Email Address</h3>
            <form name='updateEmail' method='post' action='/request/user/update-email.php' onsubmit='return validateEmail()'>
                <table>
                    <tbody>
                    <tr>
                        <td class='firstrow'>Old Email Address:</td>
                        <td>
                            <div class="field_container"><input type="text" class="inputtext" name="o" size='30' disabled value="<?= $emailAddr ?>"/></div>
                        </td>
                    </tr>
                    <tr>
                        <td class='firstrow'>New Email Address:</td>
                        <td>
                            <div class="field_container"><input type="text" class="inputtext" name="e" size='30'/></div>
                        </td>
                    </tr>
                    <tr>
                        <td class='firstrow'>New Email Address again:</td>
                        <td>
                            <div class="field_container"><input type="text" class="inputtext" name="f" size='30'/></div>
                        </td>
                    </tr>
                    <tr>
                        <td></td>
                        <td><input value="Change Email Address" type='submit' size='37'></td>
                    </tr>
                    </tbody>
                </table>
            </form>
        </div>
        <div class='component'>
            <h3>Reset Game Progress</h3>
            <?php
            echo "<table><tbody>";
            echo "<tr><td>Game:</td>";
            echo "<td><select style='width: 400px' id='resetgameselector' onchange=\"ResetFetchAwarded()\" >";
            echo "<option value=''>--</option>";
            echo "</select></td></tr>";

            echo "<tr><td>Achievement:</td>";
            echo "<td><div id='resetachievementscontrol'>";
            echo "<select style='width: 400px' id='resetachievementscontainer'></select>";    // Filled by JS
            echo "</div></td></tr>";

            echo "<tr><td></td><td><input value='Reset Progress for Selection' type='submit' onclick=\"ResetProgressForSelection()\" >";
            echo "<img id='loadingiconreset' style='opacity: 0; float: right;' src='" . asset('Images/loading.gif') . "' width='16' height='16' alt='loading icon' />";
            echo "</td></tr></tbody></table>";
            ?>
        </div>
        <?php /*
        <div class='component'>
        <h3>Reset All Achievements</h3>
            <p>Please send a message to <a href="/createmessage.php?t=RAdmin">RAdmin</a> to request a reset of your achievement progress or reset games individually above.</p>
        NOTE: deprecated - will be restored inv2
        Enter password to confirm! Please note: this is <b>not</b> reversible!
        <form method=post action="requestresetachievements.php">
        <input type="password" name="p" value="">
        <input value="Permanently Reset Achievements!" type='submit' size='67'>
        </form>
        </div>
        */ ?>
        <div class='component'>
            <h3>Delete Account</h3>
            <p>
                After requesting account deletion you may cancel your request within 14 days.<br>
                Your account's username will NOT be available after the deletion.<br>
                Your account's personal data will be cleared from the database permanently.<br>
                Content you wrote in forums, comments, etc. will NOT be removed.
            </p>
            <?php if ($userDetails['DeleteRequested']): ?>
                <p>
                    You requested to have your account deleted on <?= $userDetails['DeleteRequested'] ?> (UTC).<br>
                    Your account will be permanently deleted on <?= getDeleteDate($userDetails['DeleteRequested']) ?>.
                </p>
                <form method="post" action="/request/auth/delete-account-cancel.php" onsubmit="return confirm('Are you sure you want to cancel your account deletion request?');">
                    <input type="submit" value="Cancel account deletion request">
                </form>
            <?php else: ?>
                <form method="post" action="/request/auth/delete-account.php" onsubmit="return confirm('Are you sure you want to request account deletion?');">
                    <input type="submit" value="Request account deletion">
                </form>
            <?php endif ?>
        </div>
        </div>
    </div>
    <?php if ($permissions >= Permissions::Registered): ?>
        <div id="rightcontainer">
            <div class='component'>
                <h3>Request Score Recalculation</h3>
                <form method=post action="/request/user/recalculate-score.php">
                    <input type="hidden" name="u" value="<?= $user ?>">
                    If you feel your score is inaccurate due to point values varying during achievement development, you can request a recalculation by using the button below.<br><br>
                    <input value="Recalculate My Score" type='submit' size='37'>
                </form>
            </div>
            <div class='component'>
                <h2>Avatar</h2>
                <div style="margin-bottom: 10px">
                    New image should be less than 1MB, png/jpeg/gif supported.
                </div>
                <div style="margin-bottom: 10px">
                    <input type="file" name="file" id="uploadimagefile" onchange="return UploadNewAvatar();">
                    <img id="loadingiconavatar" style="opacity: 0; float: right;"
                         src="<?= asset('Images/loading.gif') ?>"
                         width="16" height="16" alt="loading">
                </div>
                <div style="margin-bottom: 10px">
                    After uploading, press Ctrl + F5. This refreshes your browser cache making the image visible.
                </div>
                <div style="margin-bottom: 10px">
                    Reset your avatar to default by removing your current one:
                </div>
                <form method="post" action="/request/user/remove-avatar.php" onsubmit="return confirm('Are you sure you want to permanently delete this avatar?')">
                    <input type="submit" value="Remove Avatar">
                </form>
            </div>
            <div class='component'>
                <a href="reorderSiteAwards.php">Reorder site awards</a>
            </div>
        </div>
    <?php endif ?>
</div>
<?php RenderFooter(); ?>
</body>
<?php RenderHtmlEnd(); ?>

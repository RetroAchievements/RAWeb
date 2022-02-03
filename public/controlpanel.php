<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../lib/bootstrap.php';

use RA\Permissions;
use RA\UserPref;

if (RA_ReadCookieCredentials($user, $points, $truePoints, $unreadMessageCount, $permissions)) {
    if (getAccountDetails($user, $userDetails) == false) {
        //	Immediate redirect if we cannot validate user!
        header("Location: " . getenv('APP_URL') . "?e=accountissue");
        exit;
    }
} else {
    //	Immediate redirect if we cannot validate cookie!
    header("Location: " . getenv('APP_URL') . "?e=notloggedin");
    exit;
}

//if( $user == "Scott" )
//{
//	log_email("Hi Scott! Testing!");
//	echo "Hi Scott!";
//}

$points = $userDetails['RAPoints'];
$fbUser = $userDetails['fbUser'];
$fbPrefs = $userDetails['fbPrefs'];
$websitePrefs = $userDetails['websitePrefs'];
$emailAddr = $userDetails['EmailAddress'];
$permissions = $userDetails['Permissions'];
$contribCount = $userDetails['ContribCount'];
$contribYield = $userDetails['ContribYield'];
$userWallActive = $userDetails['UserWallActive'];
$apiKey = $userDetails['APIKey'];
$userMotto = htmlspecialchars($userDetails['Motto']);

$cookie = RA_ReadCookie('RA_Cookie');
$errorCode = requestInputSanitized('e');

RenderHtmlStart();
RenderHtmlHead("My Settings");

function RenderUserPref($websitePrefs, $userPref, $setIfTrue, $state = null)
{
    echo "<input id='UserPref$userPref' type='checkbox' ";
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

    var posting = $.post('/request/user/list-games.php', {u: '<?php echo $user; ?>'});
    posting.done(OnGetAllResettableGamesList);

    $('#loadingiconreset').attr('src', '<?php echo getenv('ASSET_URL') ?>/Images/loading.gif').fadeTo(100, 1.0);
  }

  function OnGetAllResettableGamesList(data) {
    if (data !== 'ERROR3') {
      //alert( data );

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

      $('#loadingiconreset').attr('src', '<?php echo getenv('ASSET_URL') ?>/Images/tick.png').delay(750).fadeTo('slow', 0.0);
    }

    ResetFetchAwarded();
  }

  function ResetFetchAwarded() {
    var gameID = parseInt($('#resetgameselector :selected').val());
    if (gameID > 0) {
      var posting = $.post('/request/user/list-unlocks.php', {u: '<?php echo $user; ?>', g: gameID});
      posting.done(onFetchComplete);
      $('#resetachievementscontainer').empty();
      $('#warning').html('Status: Updating...');
    }
  }

  function onFetchComplete(data) {
    if (data.substr(0, 2) !== 'OK') {
      $('#warning').html('Status: Errors...');
      alert(data);
    } else {
      $('#warning').html('Status: OK...');

      var achList = data.substr(3);
      var achData = achList.split('::');

      if (achData.length > 0 && achData[0].length > 0) {
        //alert( achData );
        $('#resetachievementscontainer').append('<option value=\'9999999\' >All achievements for this game</option>');
      }

      for (var index = 0; index < achData.length; ++index) {
        var nextData = achData[index];
        var dataChunks = nextData.split('_:_');

        //alert( dataChunks );
        if (dataChunks.length < 2)
          continue;

        var achTitle = htmlEntities(dataChunks[0]);
        var achID = htmlEntities(dataChunks[1]);
        if (achID[0] == 'h') {
          //	Hardcore:
          achTitle = achTitle + ' (Hardcore)';
          $('#resetachievementscontainer').append('<option value=\'' + achID + '\'>' + achTitle + '</option>');
        } else {
          //	Casual:
          $('#resetachievementscontainer').append('<option value=\'' + achID + '\'>' + achTitle + '</option>');
        }
      }

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

      //Prompt user for confirmation if attempting to remove all achievement for a single game
      if (gameId > 0 && confirm('Reset all achievements for ' + gameName + '?')) {
        // 'All Achievements' selected: reset this game entirely!
        var gameID = $('#resetgameselector :selected').val();
        //alert( "Game ID is " + gameID );
        var posting = $.post('/request/user/reset-achievements.php', {u: '<?php echo $user; ?>', g: gameID});
        posting.done(onResetComplete);
        $('#warning').html('Status: Updating...');
        $('#loadingiconreset').attr('src', '<?php echo getenv('ASSET_URL') ?>/Images/loading.gif').fadeTo(100, 1.0);
      }
    } else if (achID > 0 && confirm('Reset achievement ' + achName + '?')) {
      // Particular achievement selected: reset just this achievement

      //alert( "Ach ID is " + achID );
      //alert( "isHardcore is " + isHardcore );
      var posting = $.post('/request/user/reset-achievements.php', {u: '<?php echo $user; ?>', a: achID, h: isHardcore});
      posting.done(onResetComplete);
      $('#warning').html('Status: Updating...');
      $('#loadingiconreset').attr('src', '<?php echo getenv('ASSET_URL') ?>/Images/loading.gif').fadeTo(100, 1.0);
    }
  }

  function onResetComplete(data) {
    if (data.substr(0, 2) !== 'OK') {
      alert(data);
    } else {
      $('#loadingiconreset').attr('src', '<?php echo getenv('ASSET_URL') ?>/Images/tick.png').delay(750).fadeTo('slow', 0.0);
      //window.location = '/controlpanel.php?e=resetok';
      if ($('#resetachievementscontainer').children('option').length > 2)
        ResetFetchAwarded();			//	Just reset ach. list
      else
        GetAllResettableGamesList();	//	last ach reset: fetch new list!
      return false;
    }
  }

  function DoChangeUserPrefs() {
    var newUserPrefs = 0;
    for (i = 0; i < 7; ++i) { // 0-6 are set if checked
      var checkbox = document.getElementById('UserPref' + i);
      if (checkbox != null && checkbox.checked)
        newUserPrefs += (1 << i);
    }

    for (i = 8; i < 15; ++i) { // 8-14 are set if checked
      var checkbox = document.getElementById('UserPref' + i);
      if (checkbox != null && checkbox.checked)
        newUserPrefs += (1 << i);
    }

    // 7 is set if unchecked
    var checkbox = document.getElementById('UserPref7');
    if (checkbox != null && !checkbox.checked)
      newUserPrefs += (1 << 7);

    $('#loadingicon').attr('src', '<?php echo getenv('ASSET_URL') ?>/Images/loading.gif').fadeTo(100, 1.0);
    var posting = $.post('/request/user/update-notification.php', {u: '<?php echo $user; ?>', p: newUserPrefs});
    posting.done(OnChangeUserPrefs);
  }

  function OnChangeUserPrefs(object) {
    //console.log( object )
    $('#loadingicon').attr('src', '<?php echo getenv('ASSET_URL') ?>/Images/tick.png').delay(750).fadeTo('slow', 0.0);
  }

  function UploadNewAvatar() {
    //	New file
    var photo = document.getElementById('uploadimagefile');
    var file = photo.files[0];

    var reader = new FileReader();
    reader.onload = function () {

      $('#loadingiconavatar').fadeTo(100, 1.0);
      $.post('/request/user/update-avatar.php', {f: file.name.split('.').pop(), i: reader.result}, onUploadImageComplete);
    };

    reader.readAsDataURL(file);
    return false;
  }

  function onUploadImageComplete(data) {
    $('#loadingiconavatar').fadeTo(100, 0.0);
    var response = JSON.parse(data);
    //if( data.substr( 0, 2 ) == "OK" )
    if (true) {
      var d = new Date();
      $('.userpic').attr('src', '/UserPic/<?php echo $user; ?>' + '.png?' + d.getTime());
    } else {
      alert(data);
    }
  }

  GetAllResettableGamesList();
</script>
<?php RenderTitleBar($user, $points, $truePoints, $unreadMessageCount, $errorCode, $permissions); ?>
<?php RenderToolbar($user, $permissions); ?>
<div id="mainpage">
    <div id="leftcontainer">
        <?php RenderErrorCodeWarning($errorCode); ?>
        <div class='component'>
            <h2>User Details</h2>
            <?php
            //	Render user panel
            echo "<p style='min-height:62px'>";
            echo "<img class='userpic' src='/UserPic/$user.png' alt='$user' style='text-align:right' width='64' height='64'>";
            echo "<strong><a href='/user/$user'>$user</a></strong> ($points points)<br>";
            echo "Account: ($permissions) " . PermissionsToString($permissions) . "<br>";
            if (!empty($userMotto) && mb_strlen($userMotto) > 1) {
                echo "<span class='usermotto'>$userMotto</span><br>";
            }
            echo "</p>";
            if ($permissions == Permissions::Unregistered) {
                echo "<div id='warning'>Warning: Email address not confirmed. Please check your inbox or spam folders, or click <a href='/request/auth/send-verification-email.php?u=$user'>here</a> to resend your verification email!</div>";
            }
            echo "<table><tbody>";
            if ($permissions >= Permissions::Registered) {
                $userMottoString = !empty($userMotto) ? $userMotto : "";
                echo "<tr>";
                echo "<td>User Motto:</td>";
                echo "<td>";
                echo "<form method='POST' action='/request/user/update-motto.php'>";
                echo "<input class='fullwidth' name='m' value=\"$userMottoString\" maxlength='49' id='usermottoinput' placeholder='Add your motto here! (No profanity please!)' />";
                echo "<input type='hidden' name='u' VALUE='$user'>";
                echo "<input type='hidden' name='c' VALUE='$cookie'>";
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
                echo "<input type='hidden' name='u' value='$user'>";
                echo "<input type='hidden' name='c' value='$cookie'>";
                echo "<input type='hidden' name='t' value='wall'>";
                echo "<input value='Save' name='submit' type='submit' size='37' />";
                echo "</form>";
                echo "</td>";
                echo "</tr>";

                echo "<tr>";
                echo "<td>Remove all comments from my User Wall:</td>";
                echo "<td>";
                echo "<form method='POST' action='/request/user/update-wall.php' onsubmit='return confirm(\"Are you sure you want to permanently delete all comment on your wall?\");'>";
                echo "<input type='hidden' name='u' value='$user'>";
                echo "<input type='hidden' name='c' value='$cookie'>";
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
            echo "<input type='hidden' name='u' value='$user'>";
            echo "<input type='hidden' name='c' value='$cookie'>";
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
            echo "<input type='hidden' name='u' value='$user'>";
            echo "<input type='hidden' name='c' value='$cookie'>";
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
                    <td><?php RenderUserPref($websitePrefs, UserPref::EmailOn_ActivityComment, true) ?></td>
                    <td><?php RenderUserPref($websitePrefs, UserPref::SiteMsgOn_ActivityComment, true) ?></td>
                </tr>
                <tr>
                    <td>If someone comments on an achievement I created:</td>
                    <td><?php RenderUserPref($websitePrefs, UserPref::EmailOn_AchievementComment, true) ?></td>
                    <td><?php RenderUserPref($websitePrefs, UserPref::SiteMsgOn_AchievementComment, true) ?></td>
                </tr>
                <tr>
                    <td>If someone comments on my user wall:</td>
                    <td><?php RenderUserPref($websitePrefs, UserPref::EmailOn_UserWallComment, true) ?></td>
                    <td><?php RenderUserPref($websitePrefs, UserPref::SiteMsgOn_UserWallComment, true) ?></td>
                </tr>
                <tr>
                    <td>If someone comments on a forum topic I'm involved in:</td>
                    <td><?php RenderUserPref($websitePrefs, UserPref::EmailOn_ForumReply, true) ?></td>
                    <td><?php RenderUserPref($websitePrefs, UserPref::SiteMsgOn_ForumReply, true) ?></td>
                </tr>
                <tr>
                    <td>If someone adds me as a friend:</td>
                    <td><?php RenderUserPref($websitePrefs, UserPref::EmailOn_AddFriend, true) ?></td>
                    <td><?php RenderUserPref($websitePrefs, UserPref::SiteMsgOn_AddFriend, true) ?></td>
                </tr>
                <tr>
                    <td>If someone sends me a private message:</td>
                    <td><?php RenderUserPref($websitePrefs, UserPref::EmailOn_PrivateMessage, true) ?></td>
                    <td><?php RenderUserPref($websitePrefs, UserPref::SiteMsgOn_PrivateMessage, true, "disabled checked") ?></td>
                </tr>
                <tr>
                    <td>With the weekly RA Newsletter:</td>
                    <td><?php RenderUserPref($websitePrefs, UserPref::EmailOn_Newsletter, true) ?></td>
                    <td><?php RenderUserPref($websitePrefs, UserPref::SiteMsgOn_Newsletter, true, "disabled") ?></td>
                </tr>
                <tr>
                    <td>When viewing a game with mature content:</td>
                    <td/>
                    <td><?php RenderUserPref($websitePrefs, UserPref::SiteMsgOff_MatureContent, false) ?></td>
                </tr>
                </tbody>
            </table>
            <img id='loadingicon' style='opacity: 0; float: right;' src='<?php echo getenv('ASSET_URL') ?>/Images/loading.gif' width='16' height='16' alt='loading icon'/>
        </div>
        <div class='component'>
            <h3>Change Password</h3>
            <?php
            if ($errorCode == 'baddata' || $errorCode == 'generalerror') {
                echo "<div id=\"warning\">Info: Errors changing your password. Please check and try again!</div>";
            } else {
                if ($errorCode == 'badnewpass') {
                    echo "<div id=\"warning\">Info: Errors changing your password, passwords too short!</div>";
                } else {
                    if ($errorCode == 'passinequal') {
                        echo "<div id=\"warning\">Info: Errors changing your password, new passwords were not identical!</div>";
                    } else {
                        if ($errorCode == 'badpassold') {
                            echo "<div id=\"warning\">Info: Errors changing your password, old password was incorrect!</div>";
                        } else {
                            if ($errorCode == 'changepassok') {
                                echo "<div id=\"warning\">Info: Password changed OK!</div>";
                            }
                        }
                    }
                }
            }
            ?>
            <form method='post' action='/request/auth/update-password.php'>
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
                    <tr>
                        <td></td>
                        <td><input type="hidden" name="u" value="<?php echo $user; ?>"></td>
                    </tr>
                    </tbody>
                </table>
            </form>
        </div>
        <div class='component'>
            <h3>Change Email Address</h3>
            <?php
            switch ($errorCode) {
                case 'e_baddata':
                case 'e_generalerror':
                    echo "<div id=\"warning\">Info: Errors changing your email address. Please check and try again!</div>";
                    break;
                case 'e_badnewemail':
                    echo "<div id=\"warning\">Info: Errors changing your email address, the new email doesn't appear to be valid!</div>";
                    break;
                case 'e_notmatch':
                    echo "<div id=\"warning\">Info: Errors changing your email address, new emails were not identical!</div>";
                    break;
                case 'e_badcredentials':
                    echo "<div id=\"warning\">Info: Errors changing your email address, session invalid. Please log out and back in, and try again!</div>";
                    break;
                case 'e_changeok':
                    echo "<div id=\"warning\">Info: Email address changed OK!</div>";
                    break;
            }
            ?>
            <form method='post' action='/request/user/update-email.php'>
                <table>
                    <tbody>
                    <tr>
                        <td class='firstrow'>Old Email Address:</td>
                        <td>
                            <div class="field_container"><input type="text" class="inputtext" size='30' disabled VALUE="<?php echo $emailAddr; ?>"/></div>
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
                <input TYPE="hidden" NAME="u" VALUE="<?php echo $user; ?>">
                <input TYPE="hidden" NAME="c" VALUE="<?php echo $cookie; ?>">
            </form>
        </div>
        <div class='component'>
            <h3>Reset Game Progress</h3>
            <?php
            echo "<table><tbody>";
            echo "<tr><td>Game:</td>";
            echo "<td><select id='resetgameselector' onchange=\"ResetFetchAwarded()\" >";
            echo "<option value=''>--</option>";
            echo "</select></td></tr>";

            echo "<tr><td>Achievement:</td>";
            echo "<td><div id='resetachievementscontrol'>";
            echo "<select id='resetachievementscontainer'></select>";    //	Filled by JS
            echo "</div></td></tr>";

            echo "<tr><td></td><td><input value='Reset Progress for Selection' type='submit' onclick=\"ResetProgressForSelection()\" >";
            echo "<img id='loadingiconreset' style='opacity: 0; float: right;' src='" . getenv('ASSET_URL') . "/Images/loading.gif' width='16' height='16' alt='loading icon' />";
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
        <INPUT TYPE="hidden" NAME="u" VALUE="<?php echo $user; ?>">
        <INPUT TYPE="password" NAME="p" VALUE="">
        <INPUT value="Permanently Reset Achievements!" type='submit' size='67'>
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
                    Your account will be permanently deleted on <?= date('Y-m-d', strtotime($userDetails['DeleteRequested']) + 60 * 60 * 24 * 14) ?>.
                </p>
                <form method="post" action="/request/auth/delete-account-cancel.php" onsubmit="return confirm('Are you sure?');">
                    <input type="submit" value="Cancel account deletion request">
                </form>
            <?php else: ?>
                <form method="post" action="/request/auth/delete-account.php" onsubmit="return confirm('Are you sure?');">
                    <input type="submit" value="Request account deletion">
                </form>
            <?php endif ?>
        </div>
    </div>
    <div id="rightcontainer">
        <div class='component'>
            <h3>Request Score Recalculation</h3>
            <form method=post action="/request/user/recalculate-score.php">
                <input TYPE="hidden" NAME="u" VALUE="<?php echo $user; ?>">
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
                     src="<?php echo getenv('ASSET_URL') ?>/Images/loading.gif"
                     width="16" height="16" alt="loading">
            </div>
            <div style="margin-bottom: 10px">
                After uploading, press Ctrl + F5. This refreshes your browser cache making the image visible.
            </div>
            <div style="margin-bottom: 10px">
                Reset your avatar to default by removing your current one:
            </div>
            <form method="post" action="/request/user/remove-avatar.php" onsubmit="return confirm('Are you sure you want to permanently delete this avatar?')">
                <input type="hidden" name="u" value="<?= $user ?>">
                <input type="submit" value="Remove Avatar">
            </form>
        </div>
        <div class='component'>
            <a href="reorderSiteAwards.php">Reorder site awards</a>
        </div>
    </div>
</div>
<?php RenderFooter(); ?>
</body>
<?php RenderHtmlEnd(); ?>

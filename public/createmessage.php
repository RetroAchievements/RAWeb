<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../lib/bootstrap.php';

$user = RA_ReadCookie('RA_User');
$cookieRaw = RA_ReadCookie('RA_Cookie');

if (RA_ReadCookieCredentials($user, $points, $truePoints, $unreadMessageCount, $permissions, \RA\Permissions::Registered)) {
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

$messageTo = requestInputSanitized('t', '');
$messageContextID = requestInputSanitized('i', -1);

$messageOutgoingPayload = requestInputSanitized('p', '');

$messageContextTitle = requestInputSanitized('s', '');
$messageContextPayload = '';
$messageContextData = null;

if ($messageContextID != -1) {
    $messageContextData = GetMessage($user, $messageContextID);
    $messageContextTitle = "RE: " . $messageContextData['Title'];
    $messageContextPayload = $messageContextData['Payload'];
    $messageContextPayload = stripslashes($messageContextPayload);
    $messageContextPayload = nl2br($messageContextPayload);
}

$errorCode = requestInputSanitized('e');

RenderHtmlStart();
RenderHtmlHead("Send Message");
?>
<body>
<script>
  $(function () {
    $(':submit').click(function (e) {
      $('.requiredinput').each(function () {
        if ($(this).val().length == 0) {
          $(this).effect('highlight', {}, 2000);
          e.preventDefault();
        }
      });
    });

    //	Focus on the first relevant field
    if ($('#messagedest').val().length == 0)
      $('#messagedest').focus();
    else
      $('#commentTextarea').focus();
  });

  function onUserChange() {
    var target = $('#messagedest').val();
    if (target.length > 2)
      $('.searchusericon').attr('src', '/UserPic/' + target + '.png');
  }

  $(document).ready(onUserChange);
</script>
<?php RenderTitleBar($user, $points, $truePoints, $unreadMessageCount, $errorCode, $permissions); ?>
<?php RenderToolbar($user, $permissions); ?>

<div id="mainpage">
    <div id='fullcontainer'>
        <div id="forums">

            <?php
            echo "<div class='navpath'>";
            echo "<a href='inbox.php'>Inbox</a>";
            echo " &raquo; <b>Send Message</b></a>";
            echo "</div>";

            echo "<h2 class='longheader'>New Message</h2>";

            if ($messageContextData !== null) {
                echo "In reply to ";
                echo GetUserAndTooltipDiv($messageContextData['UserFrom'], false);
                echo " who wrote:<br><br>";
                echo "<div class='topiccommenttext'>" . parseTopicCommentPHPBB($messageContextPayload) . "</div>";
            }

            echo "<table>";
            echo "<tbody>";

            echo "<form class='messageform' action='/request/message/send.php' method='post'>";
            echo "<input type='hidden' value='$user' name='u'>";
            echo "<input type='hidden' value='$cookieRaw' name='c'>";
            $destUser = mb_strlen($messageTo) > 2 ? $messageTo : '_User';
            echo "<tr>";
            echo "<td>User:</td>";
            echo "<td><input type='text' value='$messageTo' name='d' id='messagedest' onblur='onUserChange(); return false;' class='requiredinput searchuser'></td>";
            echo "<td style='width:10%'><img style='float:right' class='searchusericon' src='/UserPic/$destUser.png' width='64' height='64'/></td>";
            echo "</tr>";
            echo "<tr>" . "<td>Title: </td><td colspan='2'><input class='requiredinput fullwidth' type='text' value='$messageContextTitle' name='t'></td></tr>";
            echo "<tr>" . "<td>Message:</td><td colspan='2'>";

            RenderPHPBBIcons();

            echo "<textarea id='commentTextarea' class='requiredinput fullwidth forum messageTextarea' style='height:160px' rows='5' cols='61' name='m'>$messageOutgoingPayload</textarea></td></tr>";
            echo "<tr>" . "<td></td><td colspan='2' class='fullwidth'><input style='float:right' type='submit' value='Send Message' size='37'/></td></tr>";
            echo "</form>";
            echo "</tbody>";
            echo "</table>";
            ?>
            <br>
        </div> <!--  -->
    </div>
</div>
<?php RenderFooter(); ?>
</body>
<?php RenderHtmlEnd(); ?>

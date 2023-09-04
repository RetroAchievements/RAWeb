<?php

use App\Site\Enums\Permissions;
use App\Support\Shortcode\Shortcode;

if (!authenticateFromCookie($user, $permissions, $userDetails, Permissions::Registered)) {
    abort(401);
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
    sanitize_outputs($messageContextPayload);
    $messageContextPayload = Shortcode::render($messageContextPayload);
}

$messageContextTitle = htmlspecialchars($messageContextTitle, ENT_QUOTES);

RenderContentStart("Send Message");
?>
<script>
$(function () {
    // Focus on the first relevant field
    var $recipient = $('#recipient');
    if ($recipient.val().length === 0) {
        $recipient.focus();
    } else {
        $('#commentTextarea').focus();
    }
});

function onUserChange() {
    var recipient = $('#recipient').val();
    if (recipient.length > 2) {
        $('.searchusericon').attr('src', mediaAsset('/UserPic/' + recipient + '.png'));
    }
}

$(document).ready(onUserChange);
</script>
<article>
    <?php
    echo "<div class='navpath'>";
    echo "<a href='inbox.php'>Inbox</a>";
    echo " &raquo; <b>Send Message</b></a>";
    echo "</div>";

    echo "<h2>New Message</h2>";

    if ($messageContextData !== null) {
        echo "In reply to ";
        echo userAvatar($messageContextData['UserFrom']);
        echo " who wrote:<br><br>";
        echo "<div class='comment'>$messageContextPayload</div>";
    }

    echo "<form action='/request/message/send.php' method='post'>";
    echo csrf_field();

    echo "<table>";
    echo "<tbody>";

    $destUser = mb_strlen($messageTo) > 2 ? $messageTo : '_User';
    echo "<tr>";
    echo "<td>User:</td>";
    echo "<td><input type='text' value='$messageTo' name='recipient' id='recipient' onblur='onUserChange(); return false;' class='searchuser' required></td>";
    echo "<td style='width:10%'><img style='float:right' class='searchusericon' src='" . media_asset('/UserPic/' . $destUser . '.png') . "' width='64' height='64'/></td>";
    echo "</tr>";
    echo "<tr><td>Subject: </td><td colspan='2'><input class='w-full' type='text' value='$messageContextTitle' name='subject' required></td></tr>";
    echo "<tr><td>Message:</td><td colspan='2'>";
    RenderShortcodeButtons();
    echo "<textarea oninput='autoExpandTextInput(this)' id='commentTextarea' class='w-full forum messageTextarea' style='height:160px' rows='5' cols='61' name='message' placeholder='Enter your message here...' required>$messageOutgoingPayload</textarea></td></tr>";
    echo "<tr><td></td><td colspan='2' class='w-full'><button class='btn' style='float:right'>Send Message</button></td></tr>";
    echo "</tbody>";
    echo "</table>";
    echo "</form>";
    ?>
</article>
<?php RenderContentEnd(); ?>

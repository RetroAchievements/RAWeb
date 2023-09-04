<?php

use App\Support\Shortcode\Shortcode;

$outbox = requestInputSanitized('s', 0, 'integer');
$unreadOnly = (bool) request()->input('u');
$displayCount = requestInputSanitized('c', 10, 'integer');
$offset = requestInputSanitized('o', 0, 'integer');

if (!authenticateFromCookie($user, $permissions, $userDetails)) {
    abort(401);
}

if ($outbox) {
    $unreadMessageCount = 0;
    $totalMessageCount = GetSentMessageCount($user);
    $allMessages = GetSentMessages($user, $offset, $displayCount);
    RenderContentStart('Outbox');
} else {
    $unreadMessageCount = GetMessageCount($user, $totalMessageCount);
    $allMessages = GetAllMessages($user, $offset, $displayCount, $unreadOnly);
    RenderContentStart('Inbox');
}
?>
<script>
function ReadMessage(msgID) {
    var $message = $('#msgInline' + msgID);
    $message.toggle();

    if (!$message.is(':visible')) {
        return;
    }

    var $title = $('#msgInlineTitle' + msgID);
    if ($title.hasClass('message-unread')) {
        $title.removeClass('message-unread');
        $.post('/request/message/read.php', {
            message: msgID,
            status: 0
        });
    }
}

function MarkAsUnread(msgID) {
    var $title = $('#msgInlineTitle' + msgID);
    $title.toggleClass('message-unread');
    $.post('/request/message/read.php', {
        message: msgID,
        status: 1
    });
}
</script>
<article>
    <div id="globalfeed">
        <?php
        if ($outbox) {
            echo "<h2>Outbox</h2>";

            echo "<div class='mb-5'>You have <b>$totalMessageCount</b> sent messages.</div>";

            echo "<div class='flex justify-between mb-5'>";
            echo "<a class='btn btn-link' href='/inbox.php'>Inbox</a>";
            echo "<div class='flex gap-2'>";
            echo "<a class='btn btn-link' href='/createmessage.php'>Create New Message</a>";
            echo "</div>";
            echo "</div>";
        } else {
            echo "<h2>Inbox</h2>";

            echo "<div class='mb-5'>";
            echo "<div>You have <b>$unreadMessageCount</b> unread of <b>$totalMessageCount</b> total messages.</div>";
            echo "</div>";

            echo "<div class='flex justify-between mb-5'>";
            echo "<a class='btn btn-link' href='/inbox.php?s=1'>Outbox</a>";
            echo "<div class='flex gap-2'>";
            if ($unreadOnly) {
                echo "<a class='btn btn-link' href='/inbox.php'>View All Messages</a>";
            } else {
                echo "<a class='btn btn-link' href='/inbox.php?u=1'>View Unread Only</a>";
            }
            echo "<a class='btn btn-link' href='/createmessage.php'>Create New Message</a>";
            echo "</div>";
            echo "</div>";
        }

        echo "<table class='table-highlight'><tbody>";

        echo "<tr class='do-not-highlight'>";
        echo "<th>Date</th>";
        if ($outbox) {
            echo "<th colspan='2' style='min-width:150px'>To</th>";
        } else {
            echo "<th colspan='2' style='min-width:150px'>From</th>";
        }
        echo "<th style='width:100%'>Title</th>";
        echo "</tr>";

        $totalMsgs = count($allMessages);

        for ($i = 0; $i < $totalMsgs; $i++) {
            $msgID = $allMessages[$i]['ID'];
            $msgTime = $allMessages[$i]['TimeSent'];
            $msgSentAtNice = date("d/m/y, H:i ", strtotime($msgTime));
            // $msgTo      	= $allMessages[$i]['UserTo'];
            $msgTitle = $allMessages[$i]['Title'];
            $msgPayload = $allMessages[$i]['Payload'];
            $msgType = $allMessages[$i]['Type'];

            if ($outbox) {
                $msgUser = $allMessages[$i]['UserTo'];
                $msgUnread = false;
            } else {
                $msgUser = $allMessages[$i]['UserFrom'];
                $msgUnread = ($allMessages[$i]['Unread'] == 1);
            }

            sanitize_outputs(
                $msgUser,
                $msgTitle,
                $msgPayload,
            );

            $msgPayload = Shortcode::render($msgPayload);

            $styleAlt = $i % 2 == 1 ? "alt" : "";

            echo "<tr class='$styleAlt'>";

            echo "<td style='width:15%'>";
            echo "<span id='msgInlineTitleDate$msgID' title='$msgTime'>$msgSentAtNice</span>";
            echo "</td>";

            echo "<td style='width:34px'>";
            echo userAvatar($msgUser, label: false);
            echo "</td>";
            echo "<td>";
            echo userAvatar($msgUser, icon: false);
            echo "</td>";

            // echo "<td>" . $msgTo . "</td>";

            echo "<td class='cursor-pointer " . ($msgUnread ? 'message-unread' : '') . "' id='msgInlineTitle$msgID' onclick='ReadMessage($msgID)'>";
            echo $msgTitle;
            echo "</td>";

            echo "</tr>";

            echo "<tr id='msgInline$msgID' class='$styleAlt msgPayload'>";
            echo "<td colspan='4'>";
            echo "<div class='comment'>$msgPayload</div>";

            if (!$outbox) {
                echo "<div class='flex justify-end gap-2'>";

                echo "<form action='/request/message/delete.php' method='post' onsubmit='return confirm(\"Are you sure you want to permanently delete this message?\")'>";
                echo csrf_field();
                echo "<input type='hidden' name='message' value='$msgID'>";
                echo "<button class='btn btn-danger'>Delete</button>";
                echo "</form>";

                echo "<button type='button' class='btn' onclick='MarkAsUnread($msgID)'>Mark as unread</button>";

                echo "<a class='btn' href='/createmessage.php?t=$msgUser&i=$msgID'>Reply</a>";

                echo "</div>";
            }

            echo "</td>";
            echo "</tr>";
        }

        echo "</tbody></table>";

        // Get message count and build paginator URL from current GET parameters
        $messageCount = $unreadOnly ? $unreadMessageCount : $totalMessageCount;
        unset($_GET['o']);
        $urlPrefix = '/inbox.php?' . http_build_query($_GET) . '&o=';

        echo "<div class='text-right'>";
        RenderPaginator($messageCount, $displayCount, $offset, $urlPrefix);
        echo "</div>";

        ?>
    </div>
</article>
<?php RenderContentEnd(); ?>

<?php

use App\Support\Shortcode\Shortcode;

$maxCount = 10;

$offset = requestInputSanitized('o', 0, 'integer');
$count = requestInputSanitized('c', $maxCount, 'integer');
$unreadOnly = requestInputSanitized('u', 0, 'integer');
$outbox = requestInputSanitized('s', 0, 'integer');

if (!authenticateFromCookie($user, $permissions, $userDetails)) {
    abort(401);
}

if ($outbox) {
    $unreadMessageCount = 0;
    $totalMessageCount = GetSentMessageCount($user);
    $allMessages = GetSentMessages($user, $offset, $count);
    RenderContentStart('Outbox');
} else {
    $unreadMessageCount = GetMessageCount($user, $totalMessageCount);
    $allMessages = GetAllMessages($user, $offset, $count, $unreadOnly);
    RenderContentStart('Inbox');
}
?>
<script>
function MarkAsRead(msgID) {
    $('#msgInline' + msgID).toggle();

    // If was unread
    var unread = $('#msgInlineTitle' + msgID + ' span.unreadmsgtitle');
    if (unread.contents().length) {
        $.post('/request/message/read.php', {
            message: msgID,
            status: 0
        });
    }
}

function MarkAsUnread(msgID) {
    $.post('/request/message/read.php', {
        message: msgID,
        status: 1
    })
        .done(function () {
            location.reload();
        });
}
</script>
<div id="mainpage">
    <div id='fullcontainer'>
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
                    echo "<a class='btn btn-link' href='/inbox.php?u=0'>View All Messages</a>";
                } else {
                    echo "<a class='btn btn-link' href='/inbox.php?u=1'>View Unread Only</a>";
                }
                echo "<a class='btn btn-link' href='/createmessage.php'>Create New Message</a>";
                echo "</div>";
                echo "</div>";
            }

            echo "<table><tbody>";
            echo "<tr>";
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
                echo GetUserAndTooltipDiv($msgUser, true);
                echo "</td>";
                echo "<td>";
                echo GetUserAndTooltipDiv($msgUser, false);
                echo "</td>";

                // echo "<td>" . $msgTo . "</td>";

                echo "<td class='pointer' id='msgInlineTitle$msgID' onclick=\"MarkAsRead( $msgID ); return false;\">";
                if ($msgUnread) {
                    echo "<span class='unreadmsgtitle'>$msgTitle</span>";
                } else {
                    echo "<span>$msgTitle</span>";
                }
                echo "</td>";

                echo "</tr>";

                echo "<tr id='msgInline$msgID' class='$styleAlt msgPayload'>";
                echo "<td colspan='4'>";
                echo "<div class='topiccommenttext'>$msgPayload</div>";

                if (!$outbox) {
                    echo "<div class='flex justify-end gap-2'>";
                    echo "<a class='btn btn-danger' href='/request/message/delete.php?m=$msgID' onclick='return confirm(\"Are you sure you want to permanently delete this message?\")'>Delete</a>";
                    echo "<a class='btn' href='#' onclick=\"MarkAsUnread( $msgID ); return false;\" >Mark as unread</a>";
                    echo "<a class='btn btn-primary' href='/createmessage.php?t=$msgUser&amp;i=$msgID'>Reply</a>";
                    echo "</div>";
                }

                echo "</td>";
                echo "</tr>";
            }

            echo "</tbody></table>";

            echo "<div class='rightalign'>";

            if ($offset > 0) {
                echo "<span class='clickablebutton'>";
                echo "<a href='/inbox.php?o=" . ($offset - $maxCount) . "&amp;u=$unreadOnly&amp;s=$outbox'>";
                echo "&lt; Previous $maxCount";
                echo "</a>";
                echo "</span>";
            }

            if ($totalMsgs == $maxCount) {
                echo "<span class='clickablebutton'>";
                echo "<a href='/inbox.php?o=" . ($offset + $maxCount) . "&amp;u=$unreadOnly&amp;s=$outbox'>";
                echo "Next $maxCount &gt;";
                echo "</a>";
                echo "</span> ";
            }

            echo "</div>";

            echo "<br>";

            ?>
        </div>
    </div>
</div>
<?php RenderContentEnd(); ?>

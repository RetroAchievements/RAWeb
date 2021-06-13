<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../lib/bootstrap.php';

$maxCount = 10;

$errorCode = requestInputSanitized('e');
$offset = requestInputSanitized('o', 0, 'integer');
$count = requestInputSanitized('c', $maxCount, 'integer');
$unreadOnly = requestInputSanitized('u', 0, 'integer');

if (!RA_ReadCookieCredentials($user, $points, $truePoints, $unreadMessageCount, $permissions)) {
    //	Trying to visit someone's inbox while not being logged in :S
    header("Location: " . getenv('APP_URL') . "?e=notloggedin");
    exit;
}
$unreadMessageCount = GetMessageCount($user, $totalMessageCount);

$allMessages = GetAllMessages($user, $offset, $count, $unreadOnly);

getCookie($user, $cookieRaw);

RenderHtmlStart();
RenderHtmlHead('Inbox');
?>
<body>
<?php RenderTitleBar($user, $points, $truePoints, $unreadMessageCount, $errorCode, $permissions); ?>
<?php RenderToolbar($user, $permissions); ?>
<script>
  function MarkAsRead(msgID) {
    $('#msgInline' + msgID).toggle();

    //	If was unread
    var unread = $('#msgInlineTitle' + msgID + ' span.unreadmsgtitle');
    if (unread.contents().exists()) {
      var posting = $.post('/request/message/read.php', { u: '<?php echo $user; ?>', m: msgID, r: 0 });
      posting.done(onMarkAsRead);
    }
  }

  function onMarkAsRead(data) {
    if (data.substr(0, 3) == 'OK:') {
      var msgID = data.substr(3);
      var titleID = '#msgInlineTitle' + msgID;
      if ($(titleID).find('span').contents().exists()) {
        $(titleID).find('span').contents().unwrap();

        //	Reduce the number of unread messages by 1
        var numUnread = parseInt($('#messagecounttext').find('b').html());
        numUnread = numUnread - 1;
        $('#messagecounttext').find('b').html(numUnread);

        UpdateMailboxCount(numUnread);

        if (numUnread == 0) {
          if ($('#messagecountcontainer').find('big').contents().exists())
            $('#messagecountcontainer').find('big').contents().unwrap();
        }
      }
    }
  }

  function MarkAsUnread(msgID) {
    var posting = $.post('/request/message/read.php', { u: '<?php echo $user; ?>', m: msgID, r: 1 });
    posting.done(onMarkAsUnread);
  }

  function onMarkAsUnread(data) {
    if (data.substr(0, 3) == 'OK:') {
      var msgID = data.substr(3);
      $('#msgInline' + msgID).toggle();
      var titleID = '#msgInlineTitle' + msgID;

      if ($(titleID).find('span').contents().exists() == false) {
        $(titleID).contents().wrap('<span class=\'unreadmsgtitle\'>');

        //	Increase the number of unread messages by 1
        var numUnread = parseInt($('#messagecounttext').find('b').html());
        numUnread = numUnread + 1;
        $('#messagecounttext').find('b').html(numUnread);

        if (numUnread > 0) {
          if ($('#messagecountcontainer').find('big').contents().exists() == false)
            $('#messagecountcontainer').contents().wrap('<big>');
        }

        UpdateMailboxCount(numUnread);
      }
    }
  }

</script>
<div id="mainpage">
    <div id='fullcontainer'>
        <?php
        //	Left
        RenderErrorCodeWarning($errorCode);
        ?>

        <div id="globalfeed">
            <?php
            echo "<h2>Inbox</h2>";

            echo "<div id='messagecounttext'>";

            echo "<span id='messagecountcontainer'>";
            echo "<big>You have <b>$unreadMessageCount</b> unread messages</big>";
            echo "</span>";

            echo " and $totalMessageCount total messages.";

            echo "</div>";

            echo "<span class='rightalign clickablebutton'><a href='/createmessage.php'>Create New Message</a></span>";
            if ($unreadOnly) {
                echo "<span class='rightalign clickablebutton'><a href='/inbox.php?u=0'>View All Messages</a></span>";
            } else {
                echo "<span class='rightalign clickablebutton'><a href='/inbox.php?u=1'>View Unread Only</a></span>";
            }

            echo "<table class='messagestable' id='messages'><tbody>";
            echo "<tr>";
            echo "<th>Date</th>";
            echo "<th colspan='2'>From</th>";
            echo "<th>Title</th>";
            echo "</tr>";

            $totalMsgs = count($allMessages);

            for ($i = 0; $i < $totalMsgs; $i++) {
                $msgID = $allMessages[$i]['ID'];
                $msgTime = $allMessages[$i]['TimeSent'];
                $msgSentAtNice = date("d/m/y, H:i ", strtotime($msgTime));
                //$msgTo      	= $allMessages[$i]['UserTo'];
                $msgFrom = $allMessages[$i]['UserFrom'];
                $msgTitle = $allMessages[$i]['Title'];
                $msgPayload = $allMessages[$i]['Payload'];
                $msgType = $allMessages[$i]['Type'];
                $msgUnread = ($allMessages[$i]['Unread'] == 1);

                sanitize_outputs(
                    $msgFrom,
                    $msgTitle,
                    $msgPayload,
                );

                $msgPayload = nl2br($msgPayload);
                $msgPayload = stripslashes($msgPayload);
                $msgPayload = parseTopicCommentPHPBB($msgPayload);
                //$msgPayload = str_replace( '\r\n', '<br>', $msgPayload );
                //$msgPayload = str_replace( '\n', '<br>', $msgPayload );

                $styleAlt = $i % 2 == 1 ? "alt" : "";

                echo "<tr class='$styleAlt'>";

                echo "<td style='width:15%'>";
                echo "<span id='msgInlineTitleDate$msgID' title='$msgTime'>$msgSentAtNice</span>";
                echo "</td>";

                echo "<td style='width:34px'>";
                echo GetUserAndTooltipDiv($msgFrom, true);
                echo "</td>";
                echo "<td>";
                echo GetUserAndTooltipDiv($msgFrom, false);
                echo "</td>";

                //echo "<td>" . $msgTo . "</td>";

                echo "<td class='pointer' id='msgInlineTitle$msgID' onclick=\"MarkAsRead( $msgID ); return false;\">";
                echo "<span>";
                if ($msgUnread) {
                    echo "<span class='unreadmsgtitle'>$msgTitle</span>";
                } else {
                    echo "$msgTitle";
                }
                echo "</span>";
                echo "</td>";

                echo "</tr>";

                echo "<tr id='msgInline$msgID' class='$styleAlt msgPayload'>";
                echo "<td colspan='4'>";
                echo "<div class='topiccommenttext'>$msgPayload</div>";

                echo "<div class='buttoncollection rightfloat'>";
                echo "<span class='rightalign clickablebutton'><a href='#' onclick=\"MarkAsUnread( $msgID ); return false;\" >Mark as unread</a></span>";
                echo "<span class='rightalign clickablebutton'><a href='/createmessage.php?t=$msgFrom&amp;i=$msgID'>Reply</a></span>";
                echo "<span class='rightalign clickablebutton'><a href='/request/message/delete.php?u=$user&amp;c=$cookieRaw&amp;m=$msgID' onclick='return confirm(\"Are you sure you want to permanently delete this message?\")'>Delete</a></span>";
                echo "</div>";

                echo "</td>";
                echo "</tr>";
            }

            echo "</tbody></table>";

            echo "<div class='rightalign'>";

            if ($offset > 0) {
                echo "<span class='clickablebutton'>";
                echo "<a href='/inbox.php?o=" . ($offset - $maxCount) . "&amp;u=$unreadOnly'>";
                echo "&lt; Previous $maxCount";
                echo "</a>";
                echo "</span>";
            }

            if ($totalMsgs == $maxCount) {
                echo "<span class='clickablebutton'>";
                echo "<a href='/inbox.php?o=" . ($offset + $maxCount) . "&amp;u=$unreadOnly'>";
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
<?php RenderFooter(); ?>
</body>
<?php RenderHtmlEnd(); ?>


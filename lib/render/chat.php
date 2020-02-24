<?php
/**
 * @param string $user
 * @param int $chatHeight
 * @param string $chatboxat
 * @param bool $addLinkToPopOut
 */
function RenderChat($user, $chatHeight = 380, $chatboxat = '', $addLinkToPopOut = false)
{
    if (!getenv('WEBSOCKET_PORT')) {
        return;
    }

    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') {
        return;
    }

    $location = "component $chatboxat";

    $isLargeChat = ($chatboxat == 'left');

    $cssLargeSuffix = $isLargeChat ? "large" : "";
    $chatInputSize = $isLargeChat ? '95' : '39';

    echo "<div class='$location stream'>";
    echo "<h3>Chat</h3>";

    echo "<div id='chatcontainer$cssLargeSuffix' style='height:$chatHeight" . "px'>";
    echo "<div class='chatinnercontainer$cssLargeSuffix'>";
    echo "<table id='chatbox'><tbody>";

    $userPicSize = 24;

    echo "<tr id='chatloadingfirstrow'>";
    echo "<td class='chatcell'><img src='" . getenv('ASSET_URL') . "/Images/loading.gif' width='16' height='16' alt='loading icon'/></td>";
    //echo "<td class='chatcell'><img src='" . getenv('ASSET_URL') . "/Images/tick.gif' width='16' height='16' alt='loading icon'/></td>";
    echo "<td class='chatcell'></td>";
    echo "<td class='chatcellmessage'>Loading chat...</td>";
    echo "</tr>";
    echo "</tbody></table>";

    echo "</div>";
    echo "</div>";

    //echo "<div class='rightalign smallpadding'>";

    echo "<table><tbody>";
    echo "<tr>";

    if (isset($user)) {
        echo "<td class='chatcell'><a href='/User/$user'><img src='/UserPic/$user" . ".png' width='32' height='32'/></a></td>";

        echo "<td class='chatcell'>";
        echo "<div class='rightalign'>";
        echo "<input type='text' id='chatinput' maxlength='2000' size=$chatInputSize onkeydown='handleKey(event)'/>";
        echo "&nbsp;<input type='button' value='Send' onclick='sendMessage();' />";
        echo "</div>";
        echo "</td>";
    } else {
        echo "<td class='chatcell'><img src='/UserPic/_User.png' width='32' height='32' alt='default user pic'/></td>";

        echo "<td class='chatcell'>";
        echo "<div class='rightalign'>";
        echo "<input disabled readonly type='text' id='chatinput' maxlength='2000' size='39'/>";
        echo "&nbsp;<input disabled type='button' value='Send' onclick='sendMessage();' />";
        echo "</div>";
        echo "</td>";
    }

    echo "</tr>";
    echo "</tbody></table>";

    echo "<div id='sound'></div>";

    echo "<div class='rightalign'>Mute&nbsp;<input id='mutechat' type='checkbox' value='Mute' />";


    if ($addLinkToPopOut) {
        echo "&nbsp;<a href='#' onclick=\"window.open('" . str_replace('https', 'http', getenv('APP_URL')) . "/popoutchat.php', 'chat', 'status=no,height=560,width=340'); return false;\">Pop-out Chat</a>";
    }
    echo "</div>";


    //echo "<div id='tlkio' data-channel='retroachievements' data-theme='/css/chat.css' style='width:100%;height:400px;'></div><script async src='http://tlk.io/embed.js' type='text/javascript'></script>";
    echo "</div>";
    echo "<script src='/js/ping_chat.js?v=" . VERSION . "'></script>";
    echo "<script>";
    echo "initChat(50);";
    // if (!IsMobileBrowser()) {
    //     echo "initFeed();";
    // }
    echo "</script>";
}

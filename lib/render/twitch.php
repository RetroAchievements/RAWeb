<?php

function RenderTwitchTVStream($vidWidth = 300, $vidHeight = 260, $componentPos = '', $overloadVideoID = 0)
{
    echo "<div class='component $componentPos stream' >";
    echo "<h3>Twitch Stream</h3>";

    $archiveURLs = [];
    if ($componentPos == 'left') {
        $query = "SELECT *
            FROM PlaylistVideo
            ORDER BY Added DESC";

        $dbResult = s_mysql_query($query);

        while ($nextData = mysqli_fetch_assoc($dbResult)) {
            $archiveURLs[$nextData['ID']] = $nextData;
        }
    }

    if ($overloadVideoID !== 0 && isset($archiveURLs[$overloadVideoID])) {
        $vidTitle = htmlspecialchars($archiveURLs[$overloadVideoID]['Title']);
        $vidURL = $archiveURLs[$overloadVideoID]['Link'];
        $vidChapter = mb_substr($vidURL, mb_strrpos($vidURL, "/") + 1);

        $videoHTML = '<iframe
    src="https://player.twitch.tv/?' . getenv('TWITCH_CHANNEL') . '"
    height="' . $vidHeight . '"
    width="' . $vidWidth . '"
    frameborder="0"
    scrolling="no"
    allowfullscreen="true">
</iframe>';

    //$videoHTML = '<iframe src="http://player.twitch.tv/?'.getenv('TWITCH_CHANNEL').'&muted=true" height="378" width="620" frameborder="0" scrolling="no" allowfullscreen="true"></iframe>';
    } else {
        $muted = 'false';
        if (isAtHome()) {
            $muted = 'true';
        }

        $videoHTML = '<iframe src="//player.twitch.tv/?channel=' . getenv('TWITCH_CHANNEL') . '&muted=$muted" height="168" width="300" frameborder="0" scrolling="no" allowfullscreen="true"></iframe>';
    }

    echo "<div class='streamvid'>";
    echo $videoHTML;
    echo "</div>";

    //echo "<div class='streamchat'>";
    //echo "<iframe frameborder='0' scrolling='no' id='chat_embed' src='http://twitch.tv/chat/embed?channel=".getenv('TWITCH_CHANNEL')."&amp;popout_chat=true' height='$chatHeight' width='$chatWidth'></iframe>";
    //echo "</div>";

    echo "<span class='clickablebutton'><a href='//www.twitch.tv/" . getenv('TWITCH_CHANNEL') . "' class='trk'>see us on twitch.tv</a></span>";

    if ($componentPos == 'left') {
        echo "<br /><br />";
        echo "<form method='post'>";
        echo "Currently Watching:&nbsp;";
        echo "<select name='g' onchange=\"reloadTwitchContainer( this.value ); return false;\">";
        $selected = ($overloadVideoID == 0) ? 'selected' : '';
        echo "<option value='0' $selected>--Live--</option>";
        foreach ($archiveURLs as $dataElementID => $dataElementObject) {
            $vidTime = $dataElementObject['Added'];
            $niceDate = getNiceDate(strtotime($vidTime));
            $vidAuthor = $dataElementObject['Author'];
            $vidTitle = $dataElementObject['Title'];
            $vidID = $dataElementObject['ID'];
            $name = "$vidTitle ($vidAuthor, $niceDate)";
            $selected = ($overloadVideoID == $vidID) ? 'selected' : '';
            echo "<option value='$dataElementID' $selected>$name</option>";
        }
        echo "</select>";
        echo "</form>";
    }

    echo "</div>";
}

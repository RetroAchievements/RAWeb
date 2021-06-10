<?php

function RenderNewsComponent()
{
    $numNewsItems = getLatestNewsHeaders(0, 10, $newsHeaders);
    if (!$numNewsItems) {
        return;
    }

    echo "<div class='left'>";
    echo "<h2>News</h2>";
    echo "<div id='carouselcontainer' >";

    echo "<div id='carousel'>";
    for ($i = 0; $i < $numNewsItems; $i++) {
        RenderNewsHeader($newsHeaders[$i]);
    }
    echo "</div>";

    echo "<a href='#' id='ui-carousel-next'><span>next</span></a>";
    echo "<a href='#' id='ui-carousel-prev'><span>prev</span></a>";

    echo "<div id='carouselpages'></div>";

    echo "</div>";

    echo "</div>";
}

function RenderNewsHeader($newsData)
{
    $dataID = $newsData['ID'];
    $title = $newsData['Title'];
    $payload = $newsData['Payload'];
    $image = $newsData['Image'];

    $link = htmlspecialchars($newsData['Link']);

    $author = $newsData['Author'];
    $authorLink = GetUserAndTooltipDiv($author, false);
    $timestampStr = date("d M", $newsData['TimePosted']);
    $niceDate = getNiceDate($newsData['TimePosted']);

    //if( isset( $link ) )
    //else
    //    echo "<h4>$title</h4>";

    $zPos = $dataID;
    $zPos2 = $dataID + 10;

    echo "<div class='newsbluroverlay'>";
    echo "<div>";

    //echo "<span id='NEWSIMG_" . $dataID . "' class='newsimage'><img style='position: absolute; right: 0px; top:0px; width: 100%; z-index:$zPos; opacity: 0.4' src='$image' align='right' /></span>";
    //    BG
    echo "<div class='newscontainer' style='background: url(\"$image\") repeat scroll; z-index:$zPos; opacity:0.5; width: 470px; height:222px; background-size: 100% auto;' >";
    echo "</div>";

    echo "<div class='news' >";

    //    Title
    echo "<h4 style='z-index:$zPos2; position: absolute; width: 460px; top:2px; left:10px; white-space: nowrap;' ><a class='newstitle shadowoutline' href='$link'>$title</a></h4>";

    //    Text
    //echo "<small>[" . $timestampStr . "] </small>";
    echo "<div class='newstext shadowoutline' style='z-index:$zPos2; position: absolute; width: 90%; top: 40px; left:10px;'>$payload</div>";

    //    Author
    echo "<div class='newsauthor shadowoutline' style='z-index:$zPos2; position: absolute; width: 470px; top: 196px; left:0px; text-align: right'>~~$authorLink, $niceDate</div>";

    echo "</div>";
    //echo "<div style='clear:both'></div>";

    echo "</div>";
    echo "</div>";
}

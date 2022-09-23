<?php

use App\Legacy\Models\News;
use RA\LinkStyle;

function RenderNewsComponent(): void
{
    $newsData = News::orderByDesc('ID')->take(10)->get();
    if ($newsData->isEmpty()) {
        return;
    }

    echo "<div class='mb-4'>";
    echo "<h2>News</h2>";
    echo "<div id='carouselcontainer' >";

    echo "<div id='carousel'>";
    $userToolTipCache = [];
    foreach ($newsData as $news) {
        /** @var News $news */
        RenderNewsHeader($news, $userToolTipCache);
    }
    echo "</div>";

    echo "<a href='#' id='ui-carousel-next'><span>next</span></a>";
    echo "<a href='#' id='ui-carousel-prev'><span>prev</span></a>";

    echo "<div id='carouselpages'></div>";

    echo "</div>";

    echo "</div>";
}

function RenderNewsHeader(News $newsData, array &$userToolTipCache): void
{
    $title = $newsData->Title;
    $payload = $newsData->Payload;
    $image = $newsData->Image;

    $link = htmlspecialchars($newsData['Link']);

    $author = $newsData['Author'];
    $niceDate = $newsData->Timestamp->format('F j, Y, H:i');

    echo "<div class='newsbluroverlay'>";
    echo "<div>";

    // BG
    echo "<div class='newscontainer' style='background: url(\"$image\") repeat scroll; opacity:0.5; width: 470px; height:222px; background-size: 100% auto;' >";
    echo "</div>";

    echo "<div class='news' >";

    // Title
    echo "<h4 class='whitespace-nowrap absolute' style='width: 460px; top:2px; left:10px' ><a class='newstitle shadowoutline' href='$link'>$title</a></h4>";

    // Text
    echo "<div class='newstext shadowoutline absolute' style='width: 90%; top: 40px; left:10px;'>$payload</div>";

    // Author
    echo "<div class='newsauthor shadowoutline absolute' style='width: 470px; top: 196px; left:0; text-align: right'>~~";
    RenderUserLink($author, LinkStyle::Text, $userToolTipCache);
    echo ", $niceDate</div>";

    echo "</div>";

    echo "</div>";
    echo "</div>";
}

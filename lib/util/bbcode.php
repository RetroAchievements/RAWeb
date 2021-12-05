<?php

function RenderPHPBBIcons()
{
    echo "<div class='buttoncollection'>";
    echo "<span class='clickablebutton'><a href='#a' onclick='injectBBCode(\"[b]\", \"[/b]\")'><b>b</b></a></span>";
    echo "<span class='clickablebutton'><a href='#a' onclick='injectBBCode(\"[i]\", \"[/i]\")'><i>i</i></a></span>";
    echo "<span class='clickablebutton'><a href='#a' onclick='injectBBCode(\"[u]\", \"[/u]\")'><u>u</u></a></span>";
    echo "<span class='clickablebutton'><a href='#a' onclick='injectBBCode(\"[s]\", \"[/s]\")'><s>&nbsp;s&nbsp;</s></a></span>";
    echo "<span class='clickablebutton'><a href='#a' onclick='injectBBCode(\"[code]\", \"[/code]\")'><code>code</code></a></span>";
    echo "<span class='clickablebutton'><a href='#a' onclick='injectBBCode(\"[img=\", \"]\")'>img</a></span>";
    echo "<span class='clickablebutton'><a href='#a' onclick='injectBBCode(\"[url=\", \"]Link[/url]\")'>url</a></span>";
    echo "<span class='clickablebutton'><a href='#a' onclick='injectBBCode(\"[ach=\", \"]\")'>ach</a></span>";
    echo "<span class='clickablebutton'><a href='#a' onclick='injectBBCode(\"[game=\", \"]\")'>game</a></span>";
    echo "<span class='clickablebutton'><a href='#a' onclick='injectBBCode(\"[user=\", \"]\")'>user</a></span>";
    echo "<span class='clickablebutton'><a href='#a' onclick='injectBBCode(\"[spoiler]\", \"[/spoiler]\")'>spoiler</a></span>";

    echo "</div>";
}

/**
 * @param string $commentIn
 * @param bool $withImgur imgur url parsing requires the links to reliably point to mp4s - can't be static images
 * @return string|string[]|null
 */
function parseTopicCommentPHPBB($commentIn, $withImgur = false)
{
    //    Parse and format tags
    $comment = $commentIn;

    // [url]
    $comment = preg_replace(
        '~\[url=(https?://[^\]]+)\](.*?)(\[/url\])~i',
        '<a onmouseover=" Tip( \'$1\' )" onmouseout=\'UnTip()\' href=\'$1\'>$2</a>',
        $comment
    );
    $comment = preg_replace(
        '~\[url=([^\]]+)\](.*?)(\[/url\])~i',
        '<a onmouseover=" Tip( \'$1\' )" onmouseout=\'UnTip()\' href=\'https://$1\'>$2</a>',
        $comment
    );

    // [img]
    $comment = preg_replace('/(\\[img=)(.*?)(\\])/i', '<img class=\'injectinlineimage\' src=\'${2}\' />', $comment);

    if ($withImgur) {
        $comment = linkifyImgurURLs($comment);
    }
    $comment = linkifyYouTubeURLs($comment);
    $comment = linkifyTwitchURLs($comment);

    // [b]
    $comment = preg_replace('/\\[b\\](.*?)\\[\\/b\\]/is', '<b>${1}</b>', $comment);
    // [i]
    $comment = preg_replace('/\\[i\\](.*?)\\[\\/i\\]/is', '<i>${1}</i>', $comment);
    // [u]
    $comment = preg_replace('/\\[u\\](.*?)\\[\\/u\\]/is', '<u>${1}</u>', $comment);
    // [s]
    $comment = preg_replace('/\\[s\\](.*?)\\[\\/s\\]/is', '<s>${1}</s>', $comment);
    // [code]
    $comment = preg_replace('/\\[code\\](?:<br.*?>)?(.*?)\\[\\/code\\]/is', '<pre class=\'codetags\'>${1}</pre>', $comment);
    $comment = preg_replace("/\r\n|\r|\n/", '', $comment);
    // [ach]
    $comment = preg_replace_callback('/(\\[ach=)(.*?)(\\])/i', 'cb_injectAchievementPHPBB', $comment);
    // [user]
    $comment = preg_replace_callback('/(\\[user=)(.*?)(\\])/i', 'cb_injectUserPHPBB', $comment);
    // [game]
    $comment = preg_replace_callback('/(\\[game=)(.*?)(\\])/i', 'cb_injectGamePHPBB', $comment);
    // [spoiler]
    $comment = preg_replace_callback('/\\[spoiler\\](?:<br.*?>)?(.*?)\\[\\/spoiler\\]/is', 'cb_injectSpoilerPHPBB', $comment);

    $comment = linkifyBasicURLs($comment);

    return $comment;
}

function cb_injectAchievementPHPBB($matches)
{
    if (count($matches) === 0) {
        return "";
    }

    $achData = [];
    getAchievementMetadata($matches[2], $achData);
    if (empty($achData)) {
        return "";
    }
    $achID = $achData['AchievementID'];
    $achName = $achData['AchievementTitle'];
    $achDesc = $achData['Description'];
    $achPoints = $achData['Points'];
    $gameName = $achData['GameTitle'];
    $badgeName = $achData['BadgeName'];
    $consoleName = $achData['ConsoleName'];

    return GetAchievementAndTooltipDiv(
        $achID,
        $achName,
        $achDesc,
        $achPoints,
        $gameName,
        $badgeName,
        $consoleName,
        false
    );
}

//    17:05 18/04/2013
function cb_injectUserPHPBB($matches)
{
    if (count($matches) > 1) {
        $user = $matches[2];

        return GetUserAndTooltipDiv($user, false);
    }

    return "";
}

//    17:05 18/04/2013
function cb_injectGamePHPBB($matches)
{
    if (count($matches) > 1) {
        $gameID = $matches[2];
        getGameTitleFromID($gameID, $gameName, $consoleIDOut, $consoleName, $forumTopicID, $gameData);

        return GetGameAndTooltipDiv($gameID, $gameName, $gameData['GameIcon'], $consoleName);
    }

    return "";
}

function cb_injectSpoilerPHPBB($matches)
{
    if (count($matches) > 0) {
        $id = uniqid((string) mt_rand(10000, 99999));
        $spoilerBox = "<div class='devbox'>";
        $spoilerBox .= "<span onclick=\"$('#spoiler_" . $id . "').toggle(); return false;\">Spoiler (Click to show):</span><br>";
        $spoilerBox .= "<div class='spoiler' id='spoiler_" . $id . "'>";
        $spoilerBox .= $matches[1];
        $spoilerBox .= "</div>";
        $spoilerBox .= "</div>";

        return $spoilerBox;
    }

    return "";
}

function makeEmbeddedVideo($video_url)
{
    return '<div class="embed-responsive embed-responsive-16by9"><iframe class="embed-responsive-item" src="' . $video_url . '" allowfullscreen></iframe></div>';
}

/**
 * from http://stackoverflow.com/questions/5830387/how-to-find-all-youtube-video-ids-in-a-string-using-a-regex
 * @param mixed $text
 */
function linkifyYouTubeURLs($text)
{
    // http://www.youtube.com/v/YbKzgRwF91w
    // http://www.youtube.com/watch?v=1zMHaHPXqqg
    // http://youtu.be/-D06lkNS3-k
    // https://youtu.be/66ohBw9O6NU
    // https://www.youtube.com/embed/Fmwr6T2JHc4
    // https://www.youtube.com/watch?v=1YiNYWpwn7o
    // www.youtube.com/watch?v=Yjba9rvs4iU

    $text = preg_replace(
        '~
            (?:https?://)?      # Optional scheme. Either http or https.
            (?:[0-9A-Z-]+\.)?   # Optional subdomain.
            (?:                 # Group host alternatives.
              youtu\.be\/       # Either youtu.be (trailing slash required),
            | youtube\.com      # or youtube.com followed by
              \S*               # Allow anything up to VIDEO_ID,
              [^\w\-\s]         # but char before ID is non-ID char.
            )                   # End host alternatives.
            ([\w\-]{11})        # $1: VIDEO_ID is exactly 11 chars.
            (?=[^\w\-]|$)       # Assert next char is non-ID or EOS.
            (?!                 # Assert URL is not pre-linked.
              [?=&+%\w.-]*      # Allow URL (query) remainder.
              (?:               # Group pre-linked alternatives.
                [^<>]*>         # Either inside a start tag,
                | [^<>]*<\/a>   # or inside <a> element text contents.
              )                 # End recognized pre-linked alts.
            )                   # End negative lookahead assertion.
            ([?=&+%\w.-]*)      # Consume any URL (query) remainder.
        ~ix',
        makeEmbeddedVideo('//www.youtube-nocookie.com/embed/$1$2'),
        $text
    );

    return $text;
}

function linkifyTwitchURLs($text)
{
    if (mb_strpos($text, "twitch.tv") === false) {
        return $text;
    }

    $parent = parse_url(getenv('APP_URL'))['host'];

    // https://www.twitch.tv/videos/270709956
    // https://www.twitch.tv/gamingwithmist/v/40482810

    $text = preg_replace(
        '~
            (?:https?:\/\/)?    # Optional scheme. Either http or https.
            (?:www.)?           # Optional subdomain.
            (?:twitch.tv\/.*)   # Host.
            (?:videos|[^\/]+\/v)# See path examples above.
            \/([0-9]+)          # $1
            (?!                 # Assert URL is not pre-linked.
              [?=&+%\w.-]*      # Allow URL (query) remainder.
              (?:               # Group pre-linked alternatives.
                [^<>]*>         # Either inside a start tag,
                | [^<>]*<\/a>   # or inside <a> element text contents.
              )                 # End recognized pre-linked alts.
            )                   # End negative lookahead assertion.
            ([?=&+%\w.-]*)      # Consume any URL (query) remainder.
        ~ix',
        makeEmbeddedVideo('//player.twitch.tv/?video=$1&parent=' . $parent . '&autoplay=false'),
        $text
    );

    // https://www.twitch.tv/collections/cWHCMbAY1xQVDA
    $text = preg_replace(
        '~(?:https?://)?(?:www.)?twitch.tv/collections/([a-z0-9]+)~ix',
        makeEmbeddedVideo('//player.twitch.tv/?collection=$1&parent=' . $parent . '&autoplay=false'),
        $text
    );

    // https://clips.twitch.tv/AmorphousCautiousLegPanicVis
    $text = preg_replace(
        '~(?:https?://)?clips.twitch.tv/([a-z0-9]+)~i',
        makeEmbeddedVideo('//clips.twitch.tv/embed?clip=$1&parent=' . $parent . '&autoplay=false'),
        $text
    );

    return $text;
}

/**
 * see https://regex101.com/r/mQamDF/1
 * @param mixed $text
 */
function linkifyImgurURLs($text)
{
    // https://imgur.com/gallery/bciLIYm.gifv
    // https://imgur.com/a/bciLIYm.gifv
    // https://i.imgur.com/bciLIYm.gifv
    // https://i.imgur.com/bciLIYm.webm
    // https://i.imgur.com/bciLIYm.mp4

    // https://imgur.com/gallery/bciLIYm -> no extension -> will be ignored (turns out as link)
    // https://imgur.com/a/bciLIYm.gif -> replaced by gifv - potentially broken if it's a static image
    // https://imgur.com/a/bciLIYm.jpg -> downloads as gif if original is a gif, potentially large :/ can't do much about that

    $pattern = '~
        (?:https?://)?
        (?:[0-9a-z-]+\.)?
        imgur\.com
        (?:[\w/]*/)?
        (\w+)(\.\w+)?
        (?!                 # Assert URL is not pre-linked.
          [?=&+%\w.-]*      # Allow URL (query) remainder.
          (?:               # Group pre-linked alternatives.
            [^<>]*>         # Either inside a start tag,
            | [^<>]*<\/a>   # or inside <a> element text contents.
          )                 # End recognized pre-linked alts.
        )                   # End negative lookahead assertion.
        ([?=&+%\w.-]*)      # Consume any URL (query) remainder.
    ~ix';

    preg_match_all($pattern, $text, $matches);
    if (!count($matches[0])) {
        return $text;
    }
    $replacements = [];
    for ($i = 0; $i < count($matches[0]); $i++) {
        $id = $matches[1][$i];
        $extension = $matches[2][$i] ?? null;
        $extension = $extension === '.gif' ? '.gifv' : $extension;
        $replacements[$i] = $matches[0][$i];
        if (in_array($extension, ['.gifv', '.mp4', '.webm'])) {
            $replacements[$i] = '<a href="//imgur.com/' . $id . '" target="_blank" rel="noopener"><div class="embed-responsive embed-responsive-16by9"><video controls class="embed-responsive-item"><source src="//i.imgur.com/' . $id . '.mp4" type="video/mp4"></video></div><div class="text-right mb-3"><small>view on imgur</small></div></a>';
        } elseif (in_array($extension, ['.jpg', '.png', '.jpeg'])) {
            $replacements[$i] = '<a href="//imgur.com/' . $id . '" target="_blank" rel="noopener"><img class="injectinlineimage" src="//i.imgur.com/' . $id . '.jpg"><div class="text-right mb-3"><small>view on imgur</small></div></a>';
        }
    }
    $text = preg_replace_array($pattern, $replacements, $text);

    return $text;
}

function linkifyBasicURLs($text)
{
    $text = preg_replace(
        '~
            (https?://[a-z0-9_./?=&#%:+(),-]+)
            (?!                 # Assert URL is not pre-linked.
              [?=&+%\w.-]*      # Allow URL (query) remainder.
              (?:               # Group pre-linked alternatives.
                [^<>]*>         # Either inside a start tag,
                | [^<>]*<\/a>   # or inside <a> element text contents.
              )                 # End recognized pre-linked alts.
            )                   # End negative lookahead assertion.
            ([?=&+%\w.-]*)      # Consume any URL (query) remainder.
            ~ix',
        ' <a href="$1" target="_blank" rel="noopener">$1</a> ',
        $text
    );
    $text = preg_replace(
        '~
            (\s|^)
            (www\.[a-z0-9_./?=&#%:+(),-]+)
            (?!                 # Assert URL is not pre-linked.
              [?=&+%\w.-]*      # Allow URL (query) remainder.
              (?:               # Group pre-linked alternatives.
                [^<>]*>         # Either inside a start tag,
                | [^<>]*<\/a>   # or inside <a> element text contents.
              )                 # End recognized pre-linked alts.
            )                   # End negative lookahead assertion.
            ([?=&+%\w.-]*)      # Consume any URL (query) remainder.
        ~ix',
        ' <a target="_blank" href="https://$2" rel="noopener">$2</a> ',
        $text
    );

    return $text;
}

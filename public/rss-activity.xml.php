<?php

$site = config('app.url');

$dom = new DOMDocument('1.0', 'UTF-8');

$xmlRoot = $dom->createElement("rss");
$xmlRoot = $dom->appendChild($xmlRoot);

$version = $dom->createAttribute('version');
$version->value = '2.0';
$xmlRoot->appendChild($version);

$xmlns = $dom->createAttribute('xmlns:dc');
$xmlns->value = 'http://purl.org/dc/elements/1.1/';
$xmlRoot->appendChild($xmlns);

$xmlRoot2 = $dom->createElement("channel");
$xmlRoot = $xmlRoot->appendChild($xmlRoot2);

$xmlRoot->appendChild($dom->createElement('title', 'RetroAchievements.org Global Activity feed'));
$xmlRoot->appendChild($dom->createElement('description', 'RetroAchievements.org, your home for achievements in classic games'));
$xmlRoot->appendChild($dom->createElement('link', config('app.url')));

/**
 * exit early - no more feeds in v1
 */
return response(html_entity_decode($dom->saveXML()), headers: ['Content-type' => 'text/xml']);

$user = requestInputSanitized('u');
$feedtype = isset($user) ? 'friends' : 'global';
$numArticles = getFeed($user, 40, 0, $feedData, 0, $feedtype);

$feedData = array_reverse($feedData);

$lastID = 0;

for ($i = 0; $i < $numArticles; $i++) {
    $nextData = $feedData[$i];

    $article = $dom->createElement("item");
    $article = $xmlRoot->appendChild($article);

    // $newsID = $nextData['ID'];
    $user = $nextData['User'];
    $userPicURL = "$site/UserPic/$user" . ".png";
    $date = date("D, d M Y H:i:s O", $nextData['timestamp']);
    $link = config('app.url') . '/feed.php?a=' . $nextData['ID'];

    $title = getFeedItemTitle($feedData[$i], false);

    // Image first?
    // $payload = "<a href='$site/user/$user'>";
    // $payload .= "<img src='$userPicURL' width='64' height='64' />";
    $payload = "<img src=\"$userPicURL\" />";
    // $payload .= "</a>";
    $payload .= "\r\n";
    $payload .= getFeedItemTitle($feedData[$i], true);

    $title = "<![CDATA[$title]]>";
    $payload = "<![CDATA[$payload]]>";

    if ($lastID != $feedData[$i]['ID']) {
        $lastID = $feedData[$i]['ID'];
    }

    // $payload contains relative URLs, which need converting to absolute URLs
    $payload = str_replace("href='/", "href='" . config('app.url') . "/", $payload);
    $payload = str_replace("href=\"/", "href=\"" . config('app.url') . "/", $payload);
    $payload = str_replace("src='/", "src='" . config('app.url') . "/", $payload);
    $payload = str_replace("src=\"/", "src=\"" . config('app.url') . "/", $payload);

    // Strip tags from title (incl html markup :S)
    // ?!

    $article->appendChild($dom->createElement('title', htmlentities($title)));
    $article->appendChild($dom->createElement('link', $link));
    $article->appendChild($dom->createElement('description', htmlentities($payload)));
    $article->appendChild($dom->createElement('pubDate', $date));
    // $article->appendChild( $dom->createElement( 'id', $newsID ) );
    // Skip comments
    if ($feedData[$i]['Comment'] !== null) {
        while (($i < $numArticles) && $lastID == $feedData[$i]['ID']) {
            // RenderArticleComment( $feedData[$i]['ID'], $feedData[$i]['CommentUser'], $feedData[$i]['CommentPoints'], $feedData[$i]['CommentMotto'], $feedData[$i]['Comment'], $feedData[$i]['CommentPostedAt'] );
            $i++;
        }
    }
}

return response(html_entity_decode($dom->saveXML()), headers: ['Content-type' => 'text/xml']);

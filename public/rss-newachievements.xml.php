<?php

$site = config('app.url');

$dom = new DOMDocument('1.0', 'UTF-8');

$xmlRoot = $dom->createElement("rss");
$xmlRoot = $dom->appendChild($xmlRoot);

$version = $dom->createAttribute('version');
$version->value = '2.0';
$xmlRoot->appendChild($version);

$xmlRoot2 = $dom->createElement("channel");
$xmlRoot = $xmlRoot->appendChild($xmlRoot2);

$xmlns = $dom->createAttribute('xmlns:dc');
$xmlns->value = 'http://purl.org/dc/elements/1.1/';
$xmlRoot->appendChild($xmlns);

$xmlRoot->appendChild($dom->createElement('title', 'RetroAchievements.org New Achievements feed'));
$xmlRoot->appendChild($dom->createElement('description', 'RetroAchievements.org, your home for achievements in classic games'));
$xmlRoot->appendChild($dom->createElement('link', config('app.url')));

$numArticles = getLatestNewAchievements(40, $feedData);

$lastID = 0;
for ($i = 0; $i < $numArticles; $i++) {
    $nextData = $feedData[$i];

    $article = $dom->createElement("item");
    $article = $xmlRoot->appendChild($article);

    $achID = $nextData['ID'];
    $achTitle = $nextData['Title'];
    $achBadge = $nextData['BadgeName'];
    $achPoints = $nextData['Points'];
    $badgeURL = config('app.url') . "/Badge/" . $achBadge . ".png";
    $gameID = $nextData['GameID'];
    $gameTitle = $nextData['GameTitle'];
    $consoleName = $nextData['ConsoleName'];

    $date = date("D, d M Y H:i:s O", $nextData['timestamp']);
    $link = config('app.url') . '/achievement/' . $nextData['ID'];
    $simpleTitle = "$achTitle ($achPoints) ($gameTitle, $consoleName)";
    $payloadWithLinks = "<a href='$site/achievement/$achID'>$achTitle</a> ($achPoints) has been added for <a href='$site/game/$gameID'>$gameTitle</a> ($consoleName)";

    $achImage = "<a href='$site/achievement/$achID'><img src='$badgeURL' width='64' height='64' /></a>";

    $title = "<![CDATA[" . $simpleTitle . "]]>";
    $payload = "<![CDATA[" . $achImage . $payloadWithLinks . "]]>";

    if ($lastID != $nextData['ID']) {
        $lastID = $nextData['ID'];
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
}

return response(html_entity_decode($dom->saveXML()), headers: ['Content-type' => 'text/xml']);

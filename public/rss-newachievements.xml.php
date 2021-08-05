<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../lib/bootstrap.php';

$site = getenv('APP_URL');

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
$xmlRoot->appendChild($dom->createElement('link', getenv('APP_URL')));

$numArticles = getLatestNewAchievements(40, $feedData);

//var_dump( $feedData );
//$query  = "SELECT ach.ID, ach.GameID, ach.Title, ach.Description, ach.Points, gd.Title AS GameTitle, ach.DateCreated, ach.BadgeName, c.Name AS ConsoleName ";
$lastID = 0;
for ($i = 0; $i < $numArticles; $i++) {
    $nextData = $feedData[$i];

    $article = $dom->createElement("item");
    $article = $xmlRoot->appendChild($article);

    $achID = $nextData['ID'];
    $achTitle = $nextData['Title'];
    $achBadge = $nextData['BadgeName'];
    $achPoints = $nextData['Points'];
    $badgeURL = getenv('APP_URL') . "/Badge/" . $achBadge . ".png";
    $gameID = $nextData['GameID'];
    $gameTitle = $nextData['GameTitle'];
    $consoleName = $nextData['ConsoleName'];

    $date = date("D, d M Y H:i:s O", $nextData['timestamp']);
    $link = getenv('APP_URL') . '/achievement/' . $nextData['ID'];
    $simpleTitle = "$achTitle ($achPoints) ($gameTitle, $consoleName)";
    $payloadWithLinks = "<a href='$site/achievement/$achID'>$achTitle</a> ($achPoints) has been added for <a href='$site/game/$gameID'>$gameTitle</a> ($consoleName)";

    $achImage = "<a href='$site/achievement/$achID'><img src='$badgeURL' width='64' height='64' /></a>";

    $title = "<![CDATA[" . $simpleTitle . "]]>";
    $payload = "<![CDATA[" . $achImage . $payloadWithLinks . "]]>";

    if ($lastID != $nextData['ID']) {
        $lastID = $nextData['ID'];
    }

    //$payload contains relative URLs, which need converting to absolute URLs
    $payload = str_replace("href='/", "href='" . getenv('APP_URL') . "/", $payload);
    $payload = str_replace("href=\"/", "href=\"" . getenv('APP_URL') . "/", $payload);
    $payload = str_replace("src='/", "src='" . getenv('APP_URL') . "/", $payload);
    $payload = str_replace("src=\"/", "src=\"" . getenv('APP_URL') . "/", $payload);

    //	Strip tags from title (incl html markup :S)
    //	?!

    $article->appendChild($dom->createElement('title', $title));
    $article->appendChild($dom->createElement('link', $link));
    $article->appendChild($dom->createElement('description', $payload));
    $article->appendChild($dom->createElement('pubDate', $date));
}

header('Content-type: text/xml');
echo html_entity_decode($dom->saveXML());

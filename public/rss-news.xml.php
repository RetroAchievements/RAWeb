<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../lib/bootstrap.php';

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

//$xmlRoot = $dom->appendChild( $dom->createElement("channel") );

$xmlRoot->appendChild($dom->createElement('title', 'RetroAchievements.org news feed'));
$xmlRoot->appendChild($dom->createElement('description', 'RetroAchievements.org, your home for achievements in classic games'));
$xmlRoot->appendChild($dom->createElement('link', getenv('APP_URL')));

$numNews = getLatestNewsHeaders(0, 20, $newsData);

for ($i = 0; $i < $numNews; $i++) {
    $article = $dom->createElement("item");
    $article = $xmlRoot->appendChild($article);

    $newsID = $newsData[$i]['ID'];
    $newsDate = date("D, d M Y H:i:s O", $newsData[$i]['TimePosted']);
    $newsImage = $newsData[$i]['Image'];
    $newsAuthor = $newsData[$i]['Author'];
    $newsLink = getenv('APP_URL');
    $newsTitle = "<![CDATA[" . htmlspecialchars($newsData[$i]['Title']) . "]]>";

    //	Image first?
    $payload = "<a href='$newsLink'><img style='padding: 5px;' src='$newsImage' /></a>";
    $payload .= "<br>\r\n";
    $payload .= $newsData[$i]['Payload'];

    $newsPayload = "<![CDATA[" . htmlspecialchars($payload) . "]]>";

    //$newsPayload contains relative URLs, which need converting to absolute URLs
    $newsPayload = str_replace("href='/", "href='" . getenv('APP_URL') . "/", $newsPayload);
    $newsPayload = str_replace("href=\"/", "href=\"" . getenv('APP_URL') . "/", $newsPayload);
    $newsPayload = str_replace("src='/", "src='" . getenv('APP_URL') . "/", $newsPayload);
    $newsPayload = str_replace("src=\"/", "src=\"" . getenv('APP_URL') . "/", $newsPayload);

    //	Strip tags from title (incl html markup :S)
    //	?!

    $article->appendChild($dom->createElement('title', $newsTitle));
    $article->appendChild($dom->createElement('link', $newsLink));
    $article->appendChild($dom->createElement('description', $newsPayload));
    $article->appendChild($dom->createElement('pubDate', $newsDate));
    //$article->appendChild( $dom->createElement( 'id', $newsID ) );
}

header('Content-type: text/xml');
echo html_entity_decode($dom->saveXML());

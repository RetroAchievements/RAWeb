<?php

use App\Community\Models\News;

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

// $xmlRoot = $dom->appendChild( $dom->createElement("channel") );

$xmlRoot->appendChild($dom->createElement('title', 'RetroAchievements.org news feed'));
$xmlRoot->appendChild($dom->createElement('description', 'RetroAchievements.org, your home for achievements in classic games'));
$xmlRoot->appendChild($dom->createElement('link', config('app.url')));

$newsData = News::orderByDesc('ID')->take(20)->get();

foreach ($newsData as $news) {
    $article = $dom->createElement("item");
    $article = $xmlRoot->appendChild($article);

    $newsID = $news['ID'];
    $newsDate = date("D, d M Y H:i:s O", strtotime($news['Timestamp']));
    $newsImage = $news['Image'];
    $newsAuthor = $news['Author'];
    $newsLink = config('app.url');
    $newsTitle = "<![CDATA[" . htmlspecialchars(strip_tags($news['Title'])) . "]]>";

    // Image first?
    $payload = "<a href='$newsLink'><img style='padding: 5px;' src='$newsImage' /></a>";
    $payload .= "<br>\r\n";
    $payload .= $news['Payload'];

    $newsPayload = "<![CDATA[" . htmlspecialchars(strip_tags($payload)) . "]]>";

    // $newsPayload contains relative URLs, which need converting to absolute URLs
    $newsPayload = str_replace("href='/", "href='" . config('app.url') . "/", $newsPayload);
    $newsPayload = str_replace("href=\"/", "href=\"" . config('app.url') . "/", $newsPayload);
    $newsPayload = str_replace("src='/", "src='" . config('app.url') . "/", $newsPayload);
    $newsPayload = str_replace("src=\"/", "src=\"" . config('app.url') . "/", $newsPayload);

    // Strip tags from title (incl html markup :S)
    // ?!

    $article->appendChild($dom->createElement('title', htmlentities($newsTitle)));
    $article->appendChild($dom->createElement('link', $newsLink));
    $article->appendChild($dom->createElement('description', htmlentities($newsPayload)));
    $article->appendChild($dom->createElement('pubDate', $newsDate));

    $guid = $dom->createElement('guid', 'retroachievements:news:' . $newsID);
    $guid->setAttribute('isPermaLink', 'false');
    $article->appendChild($guid);
}

return response(html_entity_decode((string) $dom->saveXML()), headers: ['Content-type' => 'text/xml']);

<?php

use RA\Permissions;

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

$xmlRoot->appendChild($dom->createElement('title', 'RetroAchievements.org Forum feed'));
$xmlRoot->appendChild($dom->createElement('description', 'RetroAchievements.org, your home for achievements in classic games'));
$xmlRoot->appendChild($dom->createElement('link', config('app.url')));

$numPostsFound = getRecentForumPosts(0, 30, 120, Permissions::Registered, $recentPostsData);

$lastID = 0;

for ($i = 0; $i < $numPostsFound; $i++) {
    $nextData = $recentPostsData[$i];

    $article = $dom->createElement("item");
    $article = $xmlRoot->appendChild($article);

    $user = $nextData['Author'];
    $userPicURL = media_asset('UserPic/' . $user . '.png');
    $date = date("D, d M Y H:i:s O", strtotime($nextData['PostedAt']));
    $link = config('app.url') . '/viewtopic.php?t=' . $nextData['ForumTopicID']; // . '&amp;c=' . $nextData['CommentID'];

    $title = htmlspecialchars($nextData['ForumTopicTitle']);
    $payload = htmlspecialchars($nextData['ShortMsg'] . "...");

    $title = "<![CDATA[$title]]>";
    $payload = "<![CDATA[$payload]]>";

    $article->appendChild($dom->createElement('title', htmlentities($title)));
    $article->appendChild($dom->createElement('link', $link));
    $article->appendChild($dom->createElement('description', htmlentities($payload)));
    $article->appendChild($dom->createElement('pubDate', $date));

    $guid = $dom->createElement('guid', 'retroachievements:forum-post:'.$nextData['CommentID']);
    $guid->setAttribute('isPermaLink', 'false');
    $article->appendChild($guid);
}

return response(html_entity_decode($dom->saveXML()), headers: ['Content-type' => 'text/xml']);

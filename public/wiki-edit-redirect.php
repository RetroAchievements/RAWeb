<?php

if (!requestInputSanitized('page')) {
    exit;
}
$page = pathinfo($_GET['page'])['filename'];
if (empty($page)) {
    exit;
}
// path rewrites
switch ($page) {
    case 'index':
        $page = 'Home';
        break;
}

$repoDomainMap = [
    'docs.retroachievements.org' => 'docs',
    'guides.retroachievements.org' => 'guides',
];
$repo = $repoDomainMap[parse_url($_SERVER['HTTP_REFERER'])['host']] ?? null;

if (empty($repo)) {
    header("location: https://github.com/RetroAchievements");
    exit;
}

header("location: https://github.com/RetroAchievements/$repo/wiki/$page/_edit");

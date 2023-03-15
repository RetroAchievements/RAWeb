<?php

$page = request()->query('page');
if (empty($page) || !is_string($page)) {
    abort(400);
}

$page = pathinfo($page)['filename'];
if (empty($page)) {
    abort(400);
}

if ($page == 'index') {
    $page = 'Home';
}

$repoDomainMap = [
    'docs.retroachievements.org' => 'docs',
    'guides.retroachievements.org' => 'guides',
];
$repo = $repoDomainMap[parse_url($_SERVER['HTTP_REFERER'] ?? null)['host'] ?? null] ?? null;

if (empty($repo)) {
    return redirect('https://github.com/RetroAchievements');
}

return redirect("https://github.com/RetroAchievements/$repo/wiki/$page/_edit");

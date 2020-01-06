<?php
if (empty($_GET['page'])) {
    return;
}
$page = pathinfo($_GET['page'])['filename'];
if (empty($page)) {
    return;
}
// path rewrites
switch ($page) {
    case 'index':
        $page = 'Home';
        break;
}
header("location: https://github.com/RetroAchievements/docs/wiki/$page/_edit");

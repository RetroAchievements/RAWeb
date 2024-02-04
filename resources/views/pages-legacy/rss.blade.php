<?php

// TODO migrate to RssController::index() pages/rss.blade.php

authenticateFromCookie($user, $permissions, $userDetails);
?>
<x-app-layout pageTitle="RSS Feeds">
    <h2>RSS</h2>
    <a href="<?= url('rss-news')  ?>">
        <x-fas-rss-square />
        News
    </a>
</x-app-layout>

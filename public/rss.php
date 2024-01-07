<?php

authenticateFromCookie($user, $permissions, $userDetails);

RenderContentStart("RSS Feeds");
?>
<article>
    <div>
        <h2>RSS</h2>
        <a href="<?= url('rss-news')  ?>">
            <?= Blade::render('<x-fas-rss-square />') ?>
            News
        </a>
    </div>
</article>
<?php RenderContentEnd(); ?>

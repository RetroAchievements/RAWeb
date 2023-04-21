<?php

authenticateFromCookie($user, $permissions, $userDetails);

RenderContentStart("RSS Feeds");
?>
<div id="mainpage">
    <div id="fullcontainer">
        <div>
            <h2>RSS</h2>
            <a href="<?= url('rss-news')  ?>">
                <img src="<?= asset('assets/images/icon/rss.gif') ?>" width='41' height='13' />
                News
            </a>
        </div>
    </div>
</div>
<?php RenderContentEnd(); ?>

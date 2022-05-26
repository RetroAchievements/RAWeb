<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../lib/bootstrap.php';

authenticateFromCookie($user, $permissions, $userDetails);

$errorCode = requestInputSanitized('e');
RenderHtmlStart();
RenderHtmlHead("RSS Feeds");
?>
<body>
<?php RenderHeader($userDetails); ?>
<div id="mainpage">
    <?php
    $yOffs = 0;
    // RenderTwitchTVStream( $yOffs );
    ?>
    <div id="fullcontainer">
        <?php
        echo "<div class='navpath'>";
        echo "<b>RSS List</b>";
        echo "</div>";

        echo "<div class='largelist'>";

        echo "<h2 class='longheader'>What is RSS?</h2>";
        echo "<p>RSS allows you to easily receive a stream of the latest content and activity on a website or community, and we have a few streams available on RetroAchievements.org. ";
        echo "If you'd like to know more about RSS, visit <a href='http://www.whatisrss.com/'>What Is RSS.com</a></p>";

        echo "<h2 class='longheader'>RSS Streams</h2>";
        echo "<p>Please help yourself to the following streams. More will be added soon!<br><br>";
        echo "<a href='" . url('rss-news') . "'><img src='" . asset('Images/rss_icon.gif') . "' width='41' height='13' />&nbsp;News Stream</a><br>";
        echo "<a href='" . url('rss-newachievements') . "'><img src='" . asset('Images/rss_icon.gif') . "' width='41' height='13' />&nbsp;Newly Created Achievements</a><br>";
        echo "<a href='" . url('rss-forum') . "'><img src='" . asset('Images/rss_icon.gif') . "' width='41' height='13' />&nbsp;Forum Activity</a><br>";
        echo "<del><a href='" . url('rss-activity') . "'><img src='" . asset('Images/rss_icon.gif') . "' width='41' height='13' />&nbsp;Global Activity</a></del> (Disabled)<br>";
        if (isset($user)) {
            echo "<a href='" . url("rss-activity?u=$user") . "'><img src='" . asset('Images/rss_icon.gif') . "' width='41' height='13' />&nbsp;$user's Friends Stream</a><br>";
        }
        echo "<br></p>";

        echo "</div>";
        ?>
        <br>
    </div>
</div>
<?php RenderFooter(); ?>
</body>
<?php RenderHtmlEnd(); ?>

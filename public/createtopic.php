<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../lib/bootstrap.php';

if (RA_ReadCookieCredentials($user, $points, $truePoints, $unreadMessageCount, $permissions, \RA\Permissions::Registered)) {
    if (getAccountDetails($user, $userDetails) == false) {
        //	Immediate redirect if we cannot validate user!	//TBD: pass args?
        header("Location: " . getenv('APP_URL') . "?e=accountissue");
        exit;
    }
} else {
    //	Immediate redirect if we cannot validate cookie!	//TBD: pass args?
    header("Location: " . getenv('APP_URL') . "?e=notloggedin");
    exit;
}

$requestedForumID = requestInputQuery('f', 0, 'integer');

if ($requestedForumID == 0) {
    header("location: " . getenv('APP_URL') . "/forum.php?e=unknownforum");
    exit;
}

if (getForumDetails($requestedForumID, $forumData) == false) {
    header("location: " . getenv('APP_URL') . "/forum.php?e=unknownforum2");
    exit;
}

//var_dump( $forumData );
$thisForumID = $forumData['ID'];
$thisForumTitle = $forumData['ForumTitle'];
$thisForumDescription = $forumData['ForumDescription'];
$thisCategoryID = $forumData['CategoryID'];
$thisCategoryName = $forumData['CategoryName'];

getCookie($user, $cookieRaw);
$errorCode = requestInputSanitized('e');

RenderHtmlStart();
RenderHtmlHead("Create topic: $thisForumTitle");
?>
<body>
<?php RenderTitleBar($user, $points, $truePoints, $unreadMessageCount, $errorCode, $permissions); ?>
<?php RenderToolbar($user, $permissions); ?>
<div id="mainpage">
    <div id="leftcontainer">
        <div id="forums">
            <?php
            echo "<div class='navpath'>";
            echo "<a href='forum.php'>Forum Index</a>";
            echo " &raquo; <a href='/forum.php?c=$thisCategoryID'>$thisCategoryName</a>";
            echo " &raquo; <a href='/viewforum.php?f=$thisForumID'>$thisForumTitle</a>";
            echo " &raquo; <b>Create Topic</b></a>";
            echo "</div>";

            echo "<h2 class='longheader'>Create Topic: $thisForumTitle</h2>";

            echo "<table>";
            echo "<tbody>";

            echo "<form action='/request/forum-topic/create.php' method='post'>";
            echo "<input type='hidden' value='$cookieRaw' name='c'>";
            echo "<input type='hidden' value='$requestedForumID' name='f'>";
            echo "<tr>" . "<td>Forum:</td><td><input type='text' readonly value='$thisForumTitle'></td></tr>";
            echo "<tr>" . "<td>Author:</td><td><input type='text' readonly value='$user' name='u'></td></tr>";
            echo "<tr>" . "<td>Title:</td><td><input class='fullwidth' type='text' value='' name='t'></td></tr>";
            echo "<tr>" . "<td>Message:</td><td>";

            RenderPHPBBIcons();

            echo "<textarea id='commentTextarea' class='fullwidth forum' style='height:160px' rows=5 name='p' maxlength='60000'></textarea></td></tr>";
            echo "<tr>" . "<td></td><td class='fullwidth'><input type='submit' value='Submit new topic' SIZE='37'/></td></tr>";
            echo "</form>";
            echo "</tbody>";
            echo "</table>";
            ?>
        </div>
    </div>
    <div id="rightcontainer">
        <?php RenderRecentForumPostsComponent(4); ?>
    </div>
</div>
<?php RenderFooter(); ?>
</body>
<?php RenderHtmlEnd(); ?>

<?php
require_once __DIR__ . '/../vendor/autoload.php';

$errorCode = requestInputSanitized('e');
$newsImageInput = requestInputSanitized('g');
$newsArticleID = requestInputSanitized('n', null, 'integer');

$newsCount = getLatestNewsHeaders(0, 999, $newsData);

if (!RA_ReadCookieCredentials($user, $points, $truePoints, $unreadMessageCount, $permissions, \RA\Permissions::SuperUser)) {
    //	Immediate redirect if we cannot validate user!	//TBD: pass args?
    header("Location: " . getenv('APP_URL'));
    exit;
}

RenderHtmlStart();
RenderHtmlHead("Manage News");
?>
<body>
<?php RenderTitleBar($user, $points, $truePoints, $unreadMessageCount, $errorCode, $permissions); ?>
<?php RenderToolbar($user, $permissions); ?>
<div id="mainpage">
    <div id="fullcontainer">
        <?php
        $yOffs = 0;
        //RenderTwitchTVStream( $yOffs );

        $activeNewsArticle = null;

        RenderErrorCodeWarning($errorCode);

        echo "<div class='navpath'>";
        echo "<b>Manage News</b>";
        echo "</div>";

        echo "<div class='largelist'>"; //?
        echo "<h2 class='longheader'>How to use</h2>";
        echo "Here you can submit news to be viewed on the frontpage of the site. <b>Please</b> be considerate when posting, remember that " .
            "it will displayed on the very front page of the website, and will be the first thing a new user (and existing users) will see.<br><br>" .
            "First, it's recommended you find an associated image you can post along with the news post. You are welcome to upload " .
            "any appropriate image using 'Upload news image' below, and RA will host it for you.<br><br>" .
            "After a successful upload, you will return to this page with the image URL already added for you, along with a preview. " .
            "Note that your image will be scaled to 160px maximum width and height. " .
            "Next, pick an interesting title, and fill the article content with whatever you like!<br><br>";
        echo "<br>";

        echo "<h2 class='longheader'>Upload news image</h2>";
        echo "160px max width or height! Image size will be scaled to fit.<br>";
        echo "<form action='/request/uploadpic.php' style='padding: 2px;' method='post' enctype='multipart/form-data'>";
        echo "<label for='file'>New image:</label>";
        echo "<input type='file' name='file' id='file' />";
        echo "<input type='hidden' name='t' value='NEWS' />";
        echo "<input type='submit' name='submit' style='float: right;' value='Upload News Image' />";
        echo "</form>";
        echo "<br>";
        echo "</div>";

        echo "<h2 class='longheader'>Submit News</h2>";

        echo "Select Existing or Create New:&nbsp;";
        echo "<select name='ab' onchange=\"if (this.selectedIndex >= 0) window.location = '/submitnews.php?n=' + this.value; return false;\" >";

        echo "<option value=0>--New Post--</option>";
        //echo "<a href='/submitnews.php'>New Post</a><br>";
        for ($i = 0; $i < $newsCount; $i++) {
            $nextNews = $newsData[$i];
            $nextID = $nextNews['ID'];
            $nextTitle = $nextNews['Title'];

            $selected = ($nextID == $newsArticleID) ? "selected" : "";

            echo "<option value='$nextID' $selected><a href='/submitnews.php?n=$nextID'>$nextID - $nextTitle</a></option>";

            if ($nextNews['ID'] == $newsArticleID) {
                $activeNewsArticle = $nextNews;
            }
        }
        echo "</select><br>";
        ?>
        <br>
        <?php
        echo "<form method='post' action='/request/news/update.php'>";

        if (isset($newsArticleID) && $newsArticleID != 0) {
            echo "ID: <input type='text' name='i' size='2' value='$newsArticleID' readonly><br><br> ";
        }

        $newsTitle = "";
        if (isset($activeNewsArticle)) {
            $newsTitle = $activeNewsArticle['Title'];
        }

        $newsContent = "";
        if (isset($activeNewsArticle)) {
            $newsContent = $activeNewsArticle['Payload'];
        }

        $newsAuthor = $user;
        if (isset($activeNewsArticle)) {
            $newsAuthor = $activeNewsArticle['Author'];
        }

        $newsLink = "";
        if (isset($activeNewsArticle)) {
            $newsLink = $activeNewsArticle['Link'];
        }

        $newsImage = $newsImageInput;
        if (isset($activeNewsArticle)) {
            $newsImage = $activeNewsArticle['Image'];
        }

        echo "Title: <input type='text' name='t' style='width: 100%;' value='$newsTitle' ><br>";
        echo "<br>";
        echo "Link (optional, IMPORTANT: REMOVE the 'http://' prefix!): <input type='text' name='l' style='width: 100%;' value='$newsLink'><br>";
        echo "<br>";
        echo "Image: <input type='text' name='g' style='width: 50%;' value='$newsImage'> ";
        echo "Preview: <img src='$newsImage' /><br>";
        echo "<br>";
        echo "Article Content (most HTML supported): <br>";
        echo "<textarea rows='10' cols='80' name='p' style='width: 100%;'>$newsContent</textarea><br>";
        echo "<br>";
        echo "<input type='submit' name='submit' size='37' style='float: right;' value='Submit News Article!' />";
        echo "Author: <input type='text' name='a' value='$newsAuthor' readonly><br>";

        echo "</form><br>";
        ?>
    </div>
</div>
<?php RenderFooter(); ?>
</body>
<?php RenderHtmlEnd(); ?>

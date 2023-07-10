<?php

use App\Site\Enums\Permissions;

if (!authenticateFromCookie($user, $permissions, $userDetails, Permissions::Registered)) {
    abort(401);
}

$requestedForumID = (int) request()->query('forum');

if (empty($requestedForumID)) {
    abort(404);
}

if (!getForumDetails($requestedForumID, $forumData)) {
    abort(404);
}
if (empty($forumData)) {
    abort(404);
}

$thisForumID = $forumData['ID'];
$thisForumTitle = htmlentities($forumData['ForumTitle']);
$thisCategoryID = $forumData['CategoryID'];
$thisCategoryName = htmlentities($forumData['CategoryName']);

$existingComment = old('body');

RenderContentStart("Create topic: $thisForumTitle");
?>
<div id="mainpage">
    <div id="fullcontainer">
        <?php
        echo "<div class='navpath'>";
        echo "<a href='forum.php'>Forum Index</a>";
        echo " &raquo; <a href='/forum.php?c=$thisCategoryID'>$thisCategoryName</a>";
        echo " &raquo; <a href='/viewforum.php?f=$thisForumID'>$thisForumTitle</a>";
        echo " &raquo; <b>Create Topic</b></a>";
        echo "</div>";

        echo "<h2>Create Topic: $thisForumTitle</h2>";

        echo "<form action='/request/forum-topic/create.php' method='post'>";
        echo csrf_field();
        echo "<input type='hidden' value='$requestedForumID' name='forum'>";
        echo "<table>";
        echo "<tbody>";
        echo "<tr><td>Forum:</td><td><input type='text' readonly value='$thisForumTitle'></td></tr>";
        echo "<tr><td>Author:</td><td><input type='text' readonly value='$user'></td></tr>";
        echo "<tr><td>Topic:</td><td><input class='w-full' type='text' value='' name='title' value='" . old('title') . "'></td></tr>";
        echo "<tr><td>Message:</td><td>";
        RenderShortcodeButtons();
        ?>
        <textarea
            id="commentTextarea"
            class="w-full"
            style="height:300px"
            rows="32" cols="32"
            maxlength="60000"
            name="body"
            placeholder="Don't share links to copyrighted ROMs."
            oninput='autoExpandTextInput(this)'
        ><?= $existingComment ?></textarea>
        <?php
        echo "</td></tr>";
        echo "<tr><td></td><td>";
        echo "<div class='flex justify-between items-center'>";
        echo "<div class='textarea-counter text-right' data-textarea-id='commentTextarea'></div>";
        echo "<button class='btn'>Submit new topic</button>";
        echo "</div>";
        echo "</td></tr>";
        echo "</tbody>";
        echo "</table>";
        echo "</form>";
        ?>
    </div>
</div>
<?php RenderContentEnd(); ?>

<?php

use App\Site\Enums\Permissions;

if (!authenticateFromCookie($user, $permissions, $userDetails)) {
    abort(401);
}

$requestedComment = (int) request()->query('comment');

if (empty($requestedComment)) {
    abort(404);
}
if (!getSingleTopicComment($requestedComment, $commentData)) {
    abort(404);
}
if (empty($commentData)) {
    abort(404);
}

if ($user != $commentData['Author'] && $permissions < Permissions::Admin) {
    return back()->withErrors(__('legacy.error.permissions'));
}

if (!getTopicDetails($commentData['ForumTopicID'], $topicData)) {
    abort(404);
}
if (empty($topicData)) {
    abort(404);
}

$thisForumID = $topicData['ID'];
$thisForumTitle = htmlentities($topicData['Forum']);
$thisCategoryID = $topicData['CategoryID'];
$thisCategoryName = htmlentities($topicData['Category']);

$existingComment = old('body', $commentData['Payload']);

$thisForumTitle = htmlentities($topicData['Forum']);
$thisTopicTitle = htmlentities($topicData['TopicTitle']);
$thisTopicID = $commentData['ForumTopicID'];
$thisTopicAuthor = $topicData['Author'];
$thisAuthor = $commentData['Author'];

RenderContentStart("Edit post");
?>
<div id="mainpage">
    <div id="fullcontainer">
        <?php
        echo "<div class='navpath'>";
        echo "<a href='forum.php'>Forum Index</a>";
        echo " &raquo; <a href='/forum.php?c=$thisCategoryID'>$thisCategoryName</a>";
        echo " &raquo; <a href='/viewforum.php?f=$thisForumID'>$thisForumTitle</a>";
        echo " &raquo; <a href='/viewtopic.php?t=$thisTopicID'>$thisTopicTitle</a>";
        echo " &raquo; <b>Edit Post</b></a>";
        echo "</div>";

        echo "<h2>Edit post</h2>";

        echo "<form action='/request/forum-topic-comment/update.php' method='post'>";
        echo csrf_field();
        echo "<input type='hidden' value='$requestedComment' name='comment'>";
        echo "<table>";
        echo "<tbody>";
        echo "<tr><td>Forum:</td><td><input type='text' readonly value='$thisForumTitle'></td></tr>";
        echo "<tr><td>Author:</td><td><input type='text' readonly value='$thisAuthor'></td></tr>";
        echo "<tr><td>Topic:</td><td><input type='text' readonly class='w-full' value='$thisTopicTitle'></td></tr>";
        echo "<tr><td>Message:</td><td>";
        RenderShortcodeButtons();
        echo <<<EOF
            <textarea
                id="commentTextarea"
                class="w-full"
                style="height:300px"
                rows="32" cols="32"
                maxlength="60000"
                name="body"
                placeholder="Don't share links to copyrighted ROMs."
            >$existingComment</textarea>
        EOF;
        echo "</td></tr>";
        echo "<tr><td></td><td>";
        echo "<div class='flex justify-between items-center'>";
        echo "<div class='textarea-counter text-right' data-textarea-id='commentTextarea'></div>";
        echo "<div class='flex gap-2'>";
        echo "<a class='btn btn-link' href='/viewtopic.php?t=$thisTopicID&c=$requestedComment#$requestedComment'>Back</a>";
        echo "<button class='btn'>Submit</button>";
        echo "</div>";
        echo "</div>";
        echo "</td></tr>";
        echo "</tbody>";
        echo "</table>";
        echo "</form>";
        ?>
        <br>
    </div>
</div>
<?php RenderContentEnd(); ?>

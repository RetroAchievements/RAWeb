<?php

use App\Enums\Permissions;

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

if ($user != $commentData['Author'] && $permissions < Permissions::Moderator) {
    return back()->withErrors(__('legacy.error.permissions'));
}

if (!getTopicDetails($commentData['ForumTopicID'], $topicData)) {
    abort(404);
}
if (empty($topicData)) {
    abort(404);
}

$thisForumID = $topicData['ForumID'];
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
<article>
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
    echo "<tbody x-data='{ isValid: true }'>";
    echo "<tr><td>Forum:</td><td><input type='text' readonly value='$thisForumTitle'></td></tr>";
    echo "<tr><td>Author:</td><td><input type='text' readonly value='$thisAuthor'></td></tr>";
    echo "<tr><td>Topic:</td><td><input type='text' readonly class='w-full' value='$thisTopicTitle'></td></tr>";
    echo "<tr><td>Message:</td><td>";
    RenderShortcodeButtons();
    echo <<<HTML
        <textarea
            id="commentTextarea"
            class="w-full"
            style="height:300px"
            rows="32" cols="32"
            maxlength="60000"
            name="body"
            placeholder="Don't share links to copyrighted ROMs."
            x-on:input='isValid = window.getStringByteCount(\$event.target.value) <= 60000'
        >$existingComment</textarea>
    HTML;
    echo "</td></tr>";

    $loadingIcon = Blade::render('<x-fas-spinner id="preview-loading-icon" class="animate-spin opacity-0 transition-all duration-200 mt-1" aria-hidden="true" />');

    echo <<<HTML
        <tr>
            <td></td>
            <td>
                <div class="flex justify-between items-center">
                    <div class="textarea-counter text-right" data-textarea-id="commentTextarea"></div>

                    <div class="flex gap-2">
                        $loadingIcon
                        <a class="btn btn-link" href="/viewtopic.php?t=$thisTopicID&c=$requestedComment#$requestedComment">Back</a>
                        <button id="preview-button" type="button" class="btn" onclick="window.loadPostPreview()" :disabled="!isValid">Preview</button>
                        <button class="btn" :disabled="!isValid">Submit</button>
                    </div>
                </div>
            </td>
        </tr>
    HTML;

    echo "</tbody>";
    echo "</table>";
    echo "</form>";

    echo "<div id='post-preview'></div>";
    ?>
</article>
<?php RenderContentEnd(); ?>

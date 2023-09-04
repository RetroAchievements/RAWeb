<?php

use App\Community\Enums\SubscriptionSubjectType;
use App\Site\Enums\Permissions;
use App\Support\Shortcode\Shortcode;
use Illuminate\Support\Facades\Blade;

authenticateFromCookie($user, $permissions, $userDetails);
$userID = $userDetails['ID'] ?? 0;

// Fetch topic ID
$requestedTopicID = requestInputSanitized('t', 0, 'integer');

if ($requestedTopicID == 0) {
    abort(404);
}

getTopicDetails($requestedTopicID, $topicData);

if (empty($topicData)) {
    abort(404);
}

if ($permissions < $topicData['RequiredPermissions']) {
    abort(403);
}

// Fetch other params
$count = 15;
$offset = requestInputSanitized('o', 0, 'integer');

// Fetch 'goto comment' param if available
$gotoCommentID = requestInputSanitized('c', null, 'integer');
if (!empty($gotoCommentID)) {
    // Override $offset, just find this comment and go to it.
    getTopicCommentCommentOffset($requestedTopicID, $gotoCommentID, $count, $offset);
}

// Fetch comments
$commentList = getTopicComments($requestedTopicID, $offset, $count, $numTotalComments);

// We CANNOT have a topic with no comments... this doesn't make sense.
if (empty($commentList)) {
    abort(404);
}

$thisTopicID = $topicData['ID'];
$thisTopicID = (int) $thisTopicID;
$thisTopicAuthor = $topicData['Author'];
$thisTopicAuthorID = $topicData['AuthorID'];
$thisTopicCategory = $topicData['Category'];
$thisTopicCategoryID = $topicData['CategoryID'];
$thisTopicForum = $topicData['Forum'];
$thisTopicForumID = $topicData['ForumID'];
$thisTopicTitle = $topicData['TopicTitle'];
$thisTopicPermissions = $topicData['RequiredPermissions'];

sanitize_outputs(
    $thisTopicAuthor,
    $thisTopicCategory,
    $thisTopicForum,
    $thisTopicTitle,
);

$pageTitle = "Topic: $thisTopicForum - $thisTopicTitle";

$isSubscribed = isUserSubscribedToForumTopic($thisTopicID, $userID);

RenderContentStart($pageTitle);
?>
<article>
    <?php
    echo "<div class='navpath'>";
    echo "<a href='/forum.php'>Forum Index</a>";
    echo " &raquo; <a href='forum.php?c=$thisTopicCategoryID'>$thisTopicCategory</a>";
    echo " &raquo; <a href='viewforum.php?f=$thisTopicForumID'>$thisTopicForum</a>";
    echo " &raquo; <b>$thisTopicTitle</b></a>";
    echo "</div>";

    echo "<h2>$thisTopicTitle</h2>";

    if (isset($user) && ($thisTopicAuthor == $user || $permissions >= Permissions::Moderator)) {
        echo "<div class='devbox mb-3'>";
        echo "<span onclick=\"$('#devboxcontent').toggle(); return false;\">Options ▼</span>";
        echo "<div id='devboxcontent' style='display: none'>";

        echo "<div>Change Topic Title:</div>";
        echo "<form class='mb-3' action='/request/forum-topic/update-title.php' method='post'>";
        echo csrf_field();
        echo "<input type='hidden' name='topic' value='$thisTopicID'>";
        echo "<input type='text' name='title' value='$thisTopicTitle' size='51' >";
        echo "<button class='btn'>Submit</button>";
        echo "</form>";

        if ($permissions >= Permissions::Moderator) {
            echo "<div>Restrict Topic:</div>";
            echo "<form class='mb-3' action='/request/forum-topic/update-permissions.php' method='post'>";
            echo csrf_field();
            echo "<input type='hidden' name='topic' value='$thisTopicID'>";
            echo "<select name='permissions'>";
            foreach (Permissions::assignable() as $selectablePermission) {
                $selected = ($thisTopicPermissions == $selectablePermission) ? ' selected' : '';
                echo "<option value='$selectablePermission'$selected>" .
                    Permissions::toString($selectablePermission) . "</option>";
            }
            echo "</select>";
            echo "<button class='btn'>Change Minimum Permissions</button>";
            echo "</form>";

            echo "<form action='/request/forum-topic/delete.php' method='post' onsubmit='return confirm(\"Are you sure you want to permanently delete this topic?\")'>";
            echo csrf_field();
            echo "<input type='hidden' name='topic' value='$thisTopicID' />";
            echo "<button class='btn btn-danger'>Delete Permanently</button>";
            echo "</form>";
        }

        // TBD: Report offensive content
        // TBD: Subscribe to this topic
        // TBD: Make top-post wiki
        // if( ( $thisTopicAuthor == $user ) || $permissions >= Permissions::Developer )
        // {
        // echo "<li>Delete Topic!</li>";
        // echo "<form action='requestmodifytopic.php' >";
        // echo csrf_field();
        // echo "<input type='hidden' name='i' value='$thisTopicID' />";
        // echo "<input type='hidden' name='f' value='1' />";
        // echo "&nbsp;";
        // echo "<button class='btn' name='submit'>Delete Permanently</button>";
        // echo "</form>";
        // }

        echo "</div>";
        echo "</div>";
    }

    echo "<div class='flex justify-between mb-3'>";
    echo "<div>";
    if ($numTotalComments > $count) {
        RenderPaginator($numTotalComments, $count, $offset, "/viewtopic.php?t=$requestedTopicID&o=");
    }
    echo "</div>";
    echo "<div>";
    RenderUpdateSubscriptionForm(
        "updatetopicsubscription",
        SubscriptionSubjectType::ForumTopic,
        $thisTopicID,
        $isSubscribed
    );
    echo "</div>";
    echo "</div>";

    echo "<div class='mb-4'>";
    // Output all posts, and offer 'prev/next page'
    foreach ($commentList as $index => $commentData) {
        $nextCommentID = $commentData['ID'];
        $nextCommentPayload = $commentData['Payload'];
        $nextCommentAuthor = $commentData['Author'];
        $nextCommentIndex = ($index + 1) + $offset; // Account for the current page on the post #.

        sanitize_outputs($nextCommentPayload, $nextCommentAuthor);

        $isOriginalPoster = $nextCommentAuthor === $thisTopicAuthor;
        $isHighlighted = isset($gotoCommentID) && $nextCommentID == $gotoCommentID;
        $parsedPostContent = Shortcode::render($nextCommentPayload);

        echo Blade::render('
            <x-forum.post
                :commentData="$commentData"
                :currentUser="$user"
                :currentUserPermissions="$permissions"
                :forumTopicId="$thisTopicID"
                :isHighlighted="$isHighlighted"
                :isOriginalPoster="$isOriginalPoster"
                :parsedPostContent="$parsedPostContent"
                :threadPostNumber="$nextCommentIndex"
            />
        ', [
            'commentData' => $commentData,
            'isHighlighted' => $isHighlighted,
            'isOriginalPoster' => $isOriginalPoster,
            'nextCommentIndex' => $nextCommentIndex,
            'parsedPostContent' => $parsedPostContent,
            'permissions' => $permissions,
            'thisTopicID' => $thisTopicID,
            'user' => $user,
        ]);
    }
    echo "</div>";

    if ($numTotalComments > $count) {
        echo "<div class='mb-3'>";
        RenderPaginator($numTotalComments, $count, $offset, "/viewtopic.php?t=$requestedTopicID&o=");
        echo "</div>";
    }

    if ($user !== null && $user !== "" && $thisTopicID != 0) {
        echo "<table><tbody>";
        echo "<tr>";

        echo "<td class='align-top'>";
        echo userAvatar($user, label: false, iconSize: 64);
        echo "<br>";
        echo userAvatar($user, icon: false);
        echo "</td>";

        echo "<td class='w-full'>";

        RenderShortcodeButtons();

        $defaultMessage = ($permissions >= Permissions::Registered) ? "" : "** Your account appears to be locked. Did you confirm your email? **";
        $inputEnabled = ($permissions >= Permissions::Registered) ? "" : "disabled";
        $buttonEnabled = ($permissions >= Permissions::Registered) ? ":disabled='!isValid'" : "disabled";

        echo <<<EOF
           <script>
               function disableRepost() {
                  var btn = $('#postBtn');
                  btn.attr('disabled', true);
                  btn.html('Sending...');
               }
           </script>
        EOF;
        echo "<form action='/request/forum-topic-comment/create.php' method='post' x-data='{ isValid: true }'>";
        echo csrf_field();
        echo "<input type='hidden' name='topic' value='$thisTopicID'>";
        echo <<<HTML
            <textarea
                id="commentTextarea"
                class="w-full mb-2"
                rows="10" cols="63"
                $inputEnabled
                maxlength="60000"
                name="body"
                placeholder="Don't share links to copyrighted ROMs."
                x-on:input='autoExpandTextInput(\$el); isValid = window.getStringByteCount(\$event.target.value) <= 60000;'
            >$defaultMessage</textarea>
        HTML;

        $loadingIconSrc = asset('assets/images/icon/loading.gif');

        echo <<<HTML
            <div class="flex justify-between mb-2">
                <span class="textarea-counter" data-textarea-id="commentTextarea">0 / 60000</span>

                <div>
                    <img id="preview-loading-icon" src="$loadingIconSrc" style="opacity: 0;" width="16" height="16" alt="Loading...">
                    <button id="preview-button" type="button" class="btn" onclick="window.loadPostPreview()" $buttonEnabled>Preview</button>
                    <button id="postBtn" class="btn" onclick="this.form.submit(); disableRepost();" $buttonEnabled>Submit</button>
                </div>
            </div>
        HTML;

        echo "</form>";

        echo "</td>";
        echo "</tr>";
        echo "</tbody></table>";

        echo "</tr>";

        echo "<div id='post-preview'></div>";
        echo "</tbody></table></div>";

    } else {
        echo "<br/>You must log in before you can join this conversation.<br/>";
    }
    ?>
</article>
<?php RenderContentEnd(); ?>

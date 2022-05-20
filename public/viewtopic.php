<?php

use App\Community\Enums\SubscriptionSubjectType;
use App\Community\Enums\UserAction;
use App\Site\Enums\Permissions;
use App\Support\Shortcode\Shortcode;

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
<div id="mainpage">
    <div id="fullcontainer">
        <?php
        echo "<div class='navpath'>";
        echo "<a href='/forum.php'>Forum Index</a>";
        echo " &raquo; <a href='forum.php?c=$thisTopicCategoryID'>$thisTopicCategory</a>";
        echo " &raquo; <a href='viewforum.php?f=$thisTopicForumID'>$thisTopicForum</a>";
        echo " &raquo; <b>$thisTopicTitle</b></a>";
        echo "</div>";

        echo "<h2>$thisTopicTitle</h2>";

        if (isset($user) && ($thisTopicAuthor == $user || $permissions >= Permissions::Admin)) {
            echo "<div class='devbox mb-3'>";
            echo "<span onclick=\"$('#devboxcontent').toggle(); return false;\">Options ▼</span>";
            echo "<div id='devboxcontent' style='display: none'>";

            echo "<div>Change Topic Title:</div>";
            echo "<form class='mb-3' action='/request/forum-topic/update-title.php' method='post'>";
            echo csrf_field();
            echo "<input type='hidden' name='topic' value='$thisTopicID'>";
            echo "<input type='text' name='title' value='$thisTopicTitle' size='51' >";
            echo "<input type='submit' name='submit' value='Submit' size='37'>";
            echo "</form>";

            if ($permissions >= Permissions::Admin) {
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
                echo "<input type='submit' name='submit' value='Change Minimum Permissions' size='37'>";
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
            // echo "<input type='submit' name='submit' value='Delete Permanently' size='37' />";
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

        echo "<div class='table-wrapper'>";
        echo "<table class='table-highlight'><tbody>";
        // Output all topics, and offer 'prev/next page'
        foreach ($commentList as $commentData) {
            // Output one forum, then loop
            $nextCommentID = $commentData['ID'];
            $nextCommentPayload = $commentData['Payload'];
            $nextCommentAuthor = $commentData['Author'];
            $nextCommentAuthorID = $commentData['AuthorID'];
            $nextCommentDateCreated = $commentData['DateCreated'];
            $nextCommentDateModified = $commentData['DateModified'];
            $nextCommentAuthorised = $commentData['Authorised'];

            if ($nextCommentDateCreated !== null) {
                $nextCommentDateCreatedNiceDate = date("d M, Y H:i", strtotime($nextCommentDateCreated));
            } else {
                $nextCommentDateCreatedNiceDate = "None";
            }

            if ($nextCommentDateModified !== null) {
                $nextCommentDateModifiedNiceDate = date("d M, Y H:i", strtotime($nextCommentDateModified));
            } else {
                $nextCommentDateModifiedNiceDate = "None";
            }

            sanitize_outputs(
                $nextCommentPayload,
                $nextCommentAuthor,
            );

            $showDisclaimer = false;
            $showAuthoriseTools = false;

            if ($nextCommentAuthorised == 0) {
                // Allow, only if this is MY comment (disclaimer: unofficial), or if I'm admin (disclaimer: unofficial, verify user?)
                if ($permissions >= Permissions::Admin) {
                    // Allow with disclaimer
                    $showDisclaimer = true;
                    $showAuthoriseTools = true;
                } elseif ($nextCommentAuthor == $user) {
                    // Allow with disclaimer
                    $showDisclaimer = true;
                } else {
                    continue;    // Ignore this comment for the rest
                }
            }

            if (isset($gotoCommentID) && $nextCommentID == $gotoCommentID) {
                echo "<tr class='highlight'>";
            } else {
                echo "<tr>";
            }

            echo "<td class='align-top py-3'>";
            echo userAvatar($nextCommentAuthor, label: false, iconSize: 64);
            echo "</td>";

            echo "<td class='w-full py-3 break-all' id='$nextCommentID'>";

            echo "<div class='flex justify-between mb-2'>";
            echo "<div>";
            echo userAvatar($nextCommentAuthor, icon: false);
            if ($showDisclaimer) {
                echo " <b class='cursor-help' title='Unverified: not yet visible to the public. Please wait for a moderator to authorise this comment.'>(Unverified)</b>";
            }
            echo "<span class='smalltext text-muted ml-2'>$nextCommentDateCreatedNiceDate</span>";
            if ($nextCommentDateModified !== null) {
                echo "<i class='smalltext ml-3'>(Edited $nextCommentDateModifiedNiceDate)</i>";
            }
            echo "</div>";
            echo "<div class='flex gap-1'>";
            if ($showAuthoriseTools) {
                echo "<form action='/request/user/update.php' method='post' onsubmit='return confirm(\'Authorise this user and all their posts?\')'>";
                echo csrf_field();
                echo "<input type='hidden' name='property' value='" . UserAction::UpdateForumPostPermissions . "' />";
                echo "<input type='hidden' name='target' value='$nextCommentAuthor' />";
                echo "<input type='hidden' name='value' value='1' />";
                echo "<button class='btn py-1'>Authorise</button>";
                echo "</form>";
                echo "<form action='/request/user/update.php' method='post' onsubmit='return confirm(\'Permanently Block (spam)?\')'>";
                echo csrf_field();
                echo "<input type='hidden' name='property' value='" . UserAction::UpdateForumPostPermissions . "' />";
                echo "<input type='hidden' name='target' value='$nextCommentAuthor' />";
                echo "<input type='hidden' name='value' value='0' />";
                echo "<button class='btn btn-danger py-1'>Block</button>";
                echo "</form>";
            }
            if (($user == $nextCommentAuthor) || ($permissions >= Permissions::Admin)) {
                echo "<a class='btn btn-link py-1' href='/editpost.php?comment=$nextCommentID'>Edit</a>";
            }
            echo "<span class='btn py-1' onclick='copyToClipboard(\"" . config('app.url') . "/viewtopic.php?t=$thisTopicID&amp;c=$nextCommentID#$nextCommentID\");showStatusSuccess(\"Copied\")'>";
            echo "<img class='h-3' src='" . asset('assets/images/icon/link.png') . "'>";
            echo "</span>";
            echo "</div>";
            echo "</div>";

            echo "<div class='comment' style='word-break: normal; overflow-wrap: anywhere;'>";
            echo Shortcode::render($nextCommentPayload);
            echo "</div>";

            echo "</td>";
            echo "</tr>";
        }

        if (count($commentList) % 2 == 1) {
            echo "<tr><td colspan=2 class='smalltext'></td></tr>";
        }
        echo "</tbody></table></div>";

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

            echo <<<EOF
               <script>
                   function disableRepost() {
                      var btn = $('#postBtn');
                      btn.attr('disabled', true);
                      btn.html('Sending...');
                   }
               </script>
            EOF;
            echo "<form action='/request/forum-topic-comment/create.php' method='post'>";
            echo csrf_field();
            echo "<input type='hidden' name='topic' value='$thisTopicID'>";
            echo <<<EOF
                <textarea
                    id="commentTextarea"
                    class="w-full mb-2"
                    rows="10" cols="63"
                    $inputEnabled
                    maxlength="60000"
                    name="body"
                    placeholder="Don't share links to copyrighted ROMs."
                >$defaultMessage</textarea>
            EOF;
            echo "<div class='flex justify-between mb-2'>";
            echo "<span class='textarea-counter' data-textarea-id='commentTextarea'></span>";
            echo "<button id='postBtn' class='btn' onclick='this.form.submit(); disableRepost()' $inputEnabled>Submit</button>";    // TBD: replace with image version
            echo "</div>";
            echo "</form>";

            echo "</td>";
            echo "</tr>";
            echo "</tbody></table>";

            echo "</tr>";
            echo "</tbody></table></div>";
        } else {
            echo "<br/>You must log in before you can join this conversation.<br/>";
        }
        ?>
        <br>
    </div>
</div>
<?php RenderContentEnd(); ?>

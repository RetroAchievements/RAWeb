<?php

use RA\ForumTopicAction;
use RA\Permissions;
use RA\Shortcode;
use RA\SubscriptionSubjectType;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../lib/bootstrap.php';

authenticateFromCookie($user, $permissions, $userDetails);
$userID = $userDetails['ID'] ?? 0;

// Fetch topic ID
$requestedTopicID = requestInputSanitized('t', 0, 'integer');

if ($requestedTopicID == 0) {
    header("location: " . getenv('APP_URL') . "/forum.php?e=unknowntopic");
    exit;
}

getTopicDetails($requestedTopicID, $topicData);

if (empty($topicData)) {
    header("location: " . getenv('APP_URL') . "/forum.php?e=unknowntopic");
    exit;
}

if ($permissions < $topicData['RequiredPermissions']) {
    header("location: " . getenv('APP_URL') . "/forum.php?e=nopermission");
    exit;
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
    header("location: " . getenv('APP_URL') . "/forum.php?e=unknowntopic3");
    exit;
}

$thisTopicID = $topicData['ID'];
settype($thisTopicID, 'integer');
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

$pageTitle = "View topic: $thisTopicForum - $thisTopicTitle";

$isSubscribed = isUserSubscribedToForumTopic($thisTopicID, $userID);

$errorCode = requestInputSanitized('e');

RenderHtmlStart();
?>

<head>
    <?php RenderSharedHeader(); ?>
    <?php RenderTitleTag($pageTitle); ?>
</head>

<body>
<?php RenderHeader($userDetails); ?>

<div id="mainpage">
    <?php RenderErrorCodeWarning($errorCode); ?>
    <div id="fullcontainer">
        <?php
        echo "<div class='navpath'>";
        echo "<a href='/forum.php'>Forum Index</a>";
        echo " &raquo; <a href='forum.php?c=$thisTopicCategoryID'>$thisTopicCategory</a>";
        echo " &raquo; <a href='viewforum.php?f=$thisTopicForumID'>$thisTopicForum</a>";
        echo " &raquo; <b>$thisTopicTitle</b></a>";
        echo "</div>";

        echo "<h2 class='longheader'>$thisTopicTitle</h2>";

        if (isset($user) && ($thisTopicAuthor == $user || $permissions >= Permissions::Admin)) {
            echo "<div class='devbox'>";
            echo "<span onclick=\"$('#devboxcontent').toggle(); return false;\">Options (Click to show):</span><br>";
            echo "<div id='devboxcontent' style='display: none'>";

            echo "<div>Change Topic Title:</div>";
            echo "<form action='/request/forum-topic/modify.php' method='post' >";
            echo "<input type='text' name='v' value='$thisTopicTitle' size='51' >";
            echo "<input type='hidden' name='t' value='$thisTopicID'>";
            echo "<input type='hidden' name='f' value='" . ForumTopicAction::ModifyTitle . "'>";
            echo "<input type='submit' name='submit' value='Submit' size='37'>";
            echo "</form>";

            if ($permissions >= Permissions::Admin) {
                echo "<div>Delete Topic:</div>";
                echo "<form action='/request/forum-topic/modify.php' method='post' onsubmit='return confirm(\"Are you sure you want to permanently delete this topic?\")'>";
                echo "<input type='hidden' name='v' value='$thisTopicID' size='51' >";
                echo "<input type='hidden' name='t' value='$thisTopicID' />";
                echo "<input type='hidden' name='f' value='" . ForumTopicAction::DeleteTopic . "'>";
                echo "<input type='submit' name='submit' value='Delete Permanently' size='37'>";
                echo "</form>";

                echo "<div>Restrict Topic:</div>";
                echo "<form action='/request/forum-topic/modify.php' method='post' >";
                echo "<select name='v'>";

                foreach (Permissions::ValidUserPermissions as $selectablePermission) {
                    $selected = ($thisTopicPermissions == $selectablePermission) ? ' selected' : '';
                    echo "<option value='$selectablePermission'$selected>" .
                        Permissions::toString($selectablePermission) . "</option>";
                }

                echo "</select>";
                echo "<input type='hidden' name='t' value='$thisTopicID'>";
                echo "<input type='hidden' name='f' value='" . ForumTopicAction::ChangeRequiredPermissions . "'>";
                echo "<input type='submit' name='submit' value='Change Minimum Permissions' size='37'>";
                echo "</form>";
            }

            // TBD: Report offensive content
            // TBD: Subscribe to this topic
            // TBD: Make top-post wiki
            // if( ( $thisTopicAuthor == $user ) || $permissions >= Permissions::Developer )
            // {
            // echo "<li>Delete Topic!</li>";
            // echo "<form action='requestmodifytopic.php' >";
            // echo "<input type='hidden' name='i' value='$thisTopicID' />";
            // echo "<input type='hidden' name='f' value='1' />";
            // echo "&nbsp;";
            // echo "<input type='submit' name='submit' value='Delete Permanently' size='37' />";
            // echo "</form>";
            // }

            echo "</div>";
            echo "</div>";
        }

        echo "<div class='table-wrapper'>";
        echo "<table><tbody>";

        echo "<tr><td colspan=2>";
        if ($numTotalComments > $count) {
            RenderPaginator($numTotalComments, $count, $offset, "/viewtopic.php?t=$requestedTopicID&o=");
        }

        echo "<div class='smalltext rightfloat' style='padding: 3px'>";
        RenderUpdateSubscriptionForm(
            "updatetopicsubscription",
            SubscriptionSubjectType::ForumTopic,
            $thisTopicID,
            $isSubscribed
        );
        echo "<a href='#' onclick='document.getElementById(\"updatetopicsubscription\").submit(); return false;'>";
        echo "(" . ($isSubscribed ? "Unsubscribe" : "Subscribe") . ")";
        echo "</a>";
        echo "</div>";
        echo "</td></tr>";

        echo "<tr class='topiccommentsheader'>";
        echo "<th>Author</th>";
        echo "<th>Message</th>";
        echo "</tr>";

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

            echo "<td class='commentavatar'>";
            echo GetUserAndTooltipDiv($nextCommentAuthor, false, null, 64);
            echo "<br>";
            echo GetUserAndTooltipDiv($nextCommentAuthor, true, null, 64);
            echo "</td>";

            echo "<td class='commentpayload' id='$nextCommentID'>";

            echo "<div class='rightfloat forumlink'><img src='" . asset('Images/Link.png') . "' onclick='copy(\"" . getenv('APP_URL') . "/viewtopic.php?t=$thisTopicID&amp;c=$nextCommentID#$nextCommentID\"" . ")'</img></div>";

            echo "<div class='smalltext rightfloat'>Posted: $nextCommentDateCreatedNiceDate";

            if (($user == $nextCommentAuthor) || ($permissions >= Permissions::Admin)) {
                echo "&nbsp;<a href='/editpost.php?c=$nextCommentID'>(Edit&nbsp;Post)</a>";
            }

            if ($showDisclaimer) {
                echo "<br><span class='hoverable' title='Unverified: not yet visible to the public. Please wait for a moderator to authorise this comment.'>(Unverified)</span>";
                if ($showAuthoriseTools) {
                    // TODO should be form requests. allowing these kind of actions with get requests introduces security risks
                    echo "<br><a href='/request/user/update.php?t=$nextCommentAuthor&amp;p=1&amp;v=1'>Authorise this user and all their posts?</a>";
                    echo "<br><a href='/request/user/update.php?t=$nextCommentAuthor&amp;p=1&amp;v=0'>Permanently Block (spam)?</a>";
                }
            }

            if ($nextCommentDateModified !== null) {
                echo "<br>Last Edit: $nextCommentDateModifiedNiceDate</div>";
            }
            echo "</div>";

            echo "<br style='clear:both;'>";
            echo "<div class='topiccommenttext'>";
            echo Shortcode::render($nextCommentPayload);
            echo "</div>";
            echo "</td>";

            echo "</tr>";
        }

        if (count($commentList) % 2 == 1) {
            echo "<tr><td colspan=2 class='smalltext'></td></tr>";
        }

        if ($numTotalComments > $count) {
            echo "<tr><td colspan=2>";
            RenderPaginator($numTotalComments, $count, $offset, "/viewtopic.php?t=$requestedTopicID&o=");
            echo "</td></tr>";
        } else {
            echo "<tr><td colspan=2 class='smalltext'></td></tr>";
        }

        if ($user !== null && $user !== "" && $thisTopicID != 0) {
            echo "<tr>";

            echo "<td class='commentavatar'>";
            echo GetUserAndTooltipDiv($user, false, null, 64);
            echo "<br>";
            echo GetUserAndTooltipDiv($user, true, null, 64);
            echo "</td>";

            echo "<td class='fullwidth'>";

            RenderShortcodeButtons();

            $defaultMessage = ($permissions >= Permissions::Registered) ? "" : "** Your account appears to be locked. Did you confirm your email? **";
            $inputEnabled = ($permissions >= Permissions::Registered) ? "" : "disabled";

            echo "<form action='/request/forum-topic-comment/create.php' method='post'>";
            echo <<<EOF
                <textarea 
                    id="commentTextarea" 
                    class="fullwidth" 
                    rows="10" cols="63" 
                    $inputEnabled 
                    maxlength="60000" 
                    name="p" 
                    placeholder="Don't share links to copyrighted ROMs."
                >$defaultMessage</textarea><br>
            EOF;
            echo "<div class='textarea-counter text-right' data-textarea-id='commentTextarea'></div><br>";
            echo "<input type='hidden' name='t' value='$thisTopicID'>";
            echo "<input style='float: right' type='submit' value='Submit' $inputEnabled size='37'/>";    // TBD: replace with image version
            echo "</form>";

            echo "</td>";
            echo "</tr>";
            echo "</tbody></table>";

            echo "</tr>";
            echo "</tbody></table></div>";
        } else {
            echo "</tbody></table></div>";
            echo "<br/>You must log in before you can join this conversation.<br/>";
        }
        ?>
        <br>
    </div>
</div>
<?php RenderFooter(); ?>
</body>
<?php RenderHtmlEnd(); ?>

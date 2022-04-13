<?php

use RA\ArticleType;
use RA\Permissions;
use RA\SubscriptionSubjectType;

function RenderCommentsComponent(
    $user,
    $numComments,
    $commentData,
    $articleID,
    $articleTypeID,
    $permissions
) {
    $userID = getUserIDFromUser($user);

    echo "<div class='commentscomponent'>";

    echo "<div class='leftfloat'>";
    if ($numComments == 0) {
        echo "<i>No comments</i><br>";
    } else {
        echo "Recent comment(s):<br>";
    }
    echo "</div>";

    if (isset($user)) {
        $subjectType = SubscriptionSubjectType::fromArticleType($articleTypeID);
        if ($subjectType !== null) {
            $isSubscribed = isUserSubscribedToArticleComments($articleTypeID, $articleID, $userID);
            echo "<div class='smalltext rightfloat'>";
            RenderUpdateSubscriptionForm("updatesubscription", $subjectType, $articleID, $isSubscribed);
            echo "<a href='#' onclick='document.getElementById(\"updatesubscription\").submit(); return false;'>";
            echo "(" . ($isSubscribed ? "Unsubscribe" : "Subscribe") . ")";
            echo "</a>";
            echo "</div>";
        }
    }

    echo "<table id='feed'><tbody>";

    $lastID = 0;
    $lastKnownDate = 'Init';

    for ($i = 0; $i < $numComments; $i++) {
        $nextTime = $commentData[$i]['Submitted'];

        $dow = date("d/m", $nextTime);
        if ($lastKnownDate == 'Init') {
            $lastKnownDate = $dow;
        } elseif ($lastKnownDate !== $dow) {
            $lastKnownDate = $dow;
        }

        if ($lastID != $commentData[$i]['ID']) {
            $lastID = $commentData[$i]['ID'];
        }

        $canDeleteComments = ($articleTypeID == ArticleType::User) && ($userID == $articleID) || $permissions >= Permissions::Admin;

        RenderArticleComment(
            $articleID,
            $commentData[$i]['User'],
            $commentData[$i]['CommentPayload'],
            $commentData[$i]['Submitted'],
            $user,
            $articleTypeID,
            $commentData[$i]['ID'],
            $canDeleteComments
        );
    }

    if (isset($user)) {
        // User comment input:
        RenderCommentInputRow($user, $articleTypeID, $articleID);
    }

    echo "</tbody></table>";
    echo "<br>";

    echo "</div>";
}

function RenderArticleComment(
    $articleID,
    $user,
    $comment,
    $submittedDate,
    $localUser,
    $articleTypeID,
    $commentID,
    $allowDelete
) {
    $class = '';
    $deleteIcon = '';

    if ($user && $user == $localUser || $allowDelete) {
        $class .= ' localuser';

        $img = "<img src='" . getenv('ASSET_URL') . "/Images/cross.png' width='16' height='16' alt='delete comment'/>";
        $deleteIcon = "<div style='float: right;'><a onclick=\"removeComment($articleTypeID, $articleID, $commentID); return false;\" href='#'>$img</a></div>";
    }

    if ($user === 'Server') {
        $deleteIcon = null;
        $class .= ' system';
    }

    $artCommentID = "artcomment_{$articleTypeID}_{$articleID}_{$commentID}";
    echo "<tr class='feed_comment $class' id='$artCommentID'>";

    $niceDate = date("j M Y ", $submittedDate);
    $niceDate .= '<br>';
    $niceDate .= date("G:i", $submittedDate);

    sanitize_outputs($user, $comment);

    echo "<td class='smalldate'>$niceDate</td>";
    echo "<td class='iconscommentsingle'>";
    if ($user !== 'Server') {
        echo GetUserAndTooltipDiv($user, true);
    }
    echo "</td>";
    echo "<td class='commenttext' colspan='3'>$deleteIcon$comment</td>";

    echo "</tr>";
}

function RenderCommentInputRow($user, $articleTypeId, $articleId)
{
    sanitize_outputs($user, $formStr);
    $commentId = "art_{$articleTypeId}_{$articleId}";
    $assetUrl = getenv('ASSET_URL');

    echo <<<EOL
        <tr id="comment_$commentId">
            <td></td>
            <td class="iconscommentsingle">
                <img alt="$user" title="$user" class="badgeimg" src="/UserPic/$user.png" width="32" height="32">
            </td>
            <td colspan="3">
                <form action="/request/comment/create.php" onsubmit="onSubmitComment(event)">
                    <input type="hidden" name="a" value="$articleId">
                    <input type="hidden" name="t" value="$articleTypeId">
                    <div class="d-flex align-items-center">
                        <textarea
                            class="comment-textarea" 
                            name="c"
                            maxlength="2000"
                            placeholder="Enter a comment here..."
                            id="comment_textarea_$commentId"
                        ></textarea>
                        <button class="comment-submit-button">
                            <img src="$assetUrl/Images/Submit.png" alt="Submit">
                        </button>
                        <span class="comment-loading-indicator">
                            <img src="$assetUrl/Images/loading.gif" alt="Loading">
                        </span>
                    </div>
                    <div class="textarea-counter" data-textarea-id="comment_textarea_$commentId"></div>
                    <div class="form-error"></div>
                </form>
            </td>
        </tr>
    EOL;
}

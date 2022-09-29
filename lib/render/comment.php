<?php

use RA\ArticleType;
use RA\Permissions;
use RA\SubscriptionSubjectType;

function RenderCommentsComponent(
    ?string $user,
    int $numComments,
    array $commentData,
    int $articleID,
    int $articleTypeID,
    int $permissions
): void {
    $userID = getUserIDFromUser($user);

    echo "<div class='commentscomponent'>";

    echo "<div class='flex justify-between items-center mb-3'>";
    echo "<div>";
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
            echo "<div>";
            RenderUpdateSubscriptionForm("updatesubscription", $subjectType, $articleID, $isSubscribed, 'comments');
            echo "</div>";
        }
    }
    echo "</div>";

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

        $canDeleteComments = $articleTypeID == ArticleType::User && $userID == $articleID || $permissions >= Permissions::Admin;

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
): void {
    $class = '';
    $deleteIcon = '';

    if ($user && $user == $localUser || $allowDelete) {
        $img = "<img src='" . asset('assets/images/icon/cross.png') . "' width='16' height='16' alt='delete comment'/>";
        $deleteIcon = "<div style='float: right;'><a onclick=\"removeComment($articleTypeID, $articleID, $commentID); return false;\" href='#'>$img</a></div>";
    }

    if ($user === 'Server') {
        $deleteIcon = null;
        $class .= ' system';
    }

    $artCommentID = "artcomment_{$articleTypeID}_{$articleID}_{$commentID}";
    echo "<tr class='comment$class' id='$artCommentID'>";

    $niceDate = date("j M Y ", $submittedDate);
    $niceDate .= date("H:i", $submittedDate);

    sanitize_outputs($user, $comment);
    $comment = nl2br($comment);

    echo "<td>";
    if ($user !== 'Server') {
        echo userAvatar($user, label: false);
    }
    echo "</td>";
    echo "<td class='w-full' colspan='3'>";
    echo $deleteIcon;
    echo "<div>";
    if ($user !== 'Server') {
        echo userAvatar($user, label: true);
    }
    echo " <span class='smalldate'>$niceDate</span>";
    echo "</div>";

    echo "<div style='word-break: break-word'>";
    echo $comment;
    echo "</div>";
    echo "</td>";

    echo "</tr>";
}

function RenderCommentInputRow($user, $articleTypeId, $articleId): void
{
    sanitize_outputs($user, $formStr);
    $commentId = "art_{$articleTypeId}_{$articleId}";
    $submitImageUrl = asset('assets/images/icon/submit.png');
    $loadingImageUrl = asset('assets/images/icon/loading.gif');
    $csrfField = csrf_field();
    $avatar = media_asset("/UserPic/$user.png");

    echo <<<EOL
        <tr id="comment_$commentId">
            <td>
                <img alt="$user" title="$user" class="badgeimg" src="$avatar" width="32" height="32">
            </td>
            <td colspan="3">
                <form action="/request/comment/create.php" method="post">
                    $csrfField
                    <input type="hidden" name="commentable_id" value="$articleId">
                    <input type="hidden" name="commentable_type" value="$articleTypeId">
                    <div class="flex align-center mb-1">
                        <textarea
                            class="comment-textarea"
                            name="body"
                            maxlength="2000"
                            placeholder="Enter a comment here..."
                            id="comment_textarea_$commentId"
                        ></textarea>
                        <button class="comment-submit-button">
                            <img src="$submitImageUrl" alt="Submit">
                        </button>
                        <span class="comment-loading-indicator">
                            <img src="$loadingImageUrl" alt="Loading">
                        </span>
                    </div>
                    <div class="textarea-counter" data-textarea-id="comment_textarea_$commentId"></div>
                    <div class="text-danger hidden"></div>
                </form>
            </td>
        </tr>
    EOL;
}

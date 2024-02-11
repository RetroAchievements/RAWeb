<?php

use App\Community\Enums\ArticleType;
use App\Community\Enums\SubscriptionSubjectType;
use App\Enums\Permissions;
use Illuminate\Support\Facades\Blade;

function RenderCommentsComponent(
    ?string $user,
    int $numComments,
    array $commentData,
    int $articleID,
    int $articleTypeID,
    int $permissions,
    int $count = 20,
    int $offset = 0,
    bool $embedded = true
): void {
    $userID = getUserIDFromUser($user);

    echo "<div class='commentscomponent'>";

    echo "<div class='flex justify-between items-center mb-3'>";
    echo "<div>";
    if ($numComments == 0) {
        echo "<i>No comments</i>";
    } elseif (!$embedded) {
        if ($numComments > $count) {
            RenderPaginator($numComments, $count, $offset, "/comments.php?t=$articleTypeID&i=$articleID&o=");
        }
    } elseif ($numComments > count($commentData)) {
        echo "<div class='hidden sm:block'>";
        echo "Recent comments: <span class='smalltext'>(<a href='/comments.php?t=$articleTypeID&i=$articleID'>All $numComments</a>)</span>";
        echo "</div>";

        echo "<div class='sm:hidden'>";
        echo " <p>Recent comments</p>";
        echo " <p class='smalltext'>(<a href='/comments.php?t=$articleTypeID&i=$articleID'>See all $numComments</a>)</p>";
        echo "</div>";
    } else {
        echo "Comments:";
    }
    echo "</div>";
    if (isset($user)) {
        $subjectType = SubscriptionSubjectType::fromArticleType($articleTypeID);
        if ($subjectType !== null) {
            $isSubscribed = isUserSubscribedToArticleComments($articleTypeID, $articleID, $userID);
            echo "<div>";
            RenderUpdateSubscriptionForm(
                'updatesubscription',
                $subjectType,
                $articleID,
                $isSubscribed,
                $embedded ? 'comments' : null
            );
            echo "</div>";
        }
    }
    echo "</div>";

    echo "<table id='feed' class='table-highlight'><tbody>";

    $lastID = 0;
    $lastKnownDate = 'Init';

    foreach ($commentData as $comment) {
        if (isset($comment['banned_at']) && $permissions < Permissions::Moderator) {
            continue;
        }

        $dow = date("d/m", (int) $comment['Submitted']);
        if ($lastKnownDate == 'Init') {
            $lastKnownDate = $dow;
        } elseif ($lastKnownDate !== $dow) {
            $lastKnownDate = $dow;
        }

        if ($lastID != $comment['ID']) {
            $lastID = $comment['ID'];
        }

        $canDeleteComment = $articleTypeID == ArticleType::User && $userID === $articleID || $permissions >= Permissions::Moderator;

        RenderArticleComment(
            $articleID,
            $comment['User'],
            $comment['CommentPayload'],
            // TODO no unix timestamp here
            (int) $comment['Submitted'],
            $user,
            $articleTypeID,
            (int) $comment['ID'],
            $canDeleteComment
        );
    }

    if (isset($user)) {
        // User comment input:
        RenderCommentInputRow($user, $articleTypeID, $articleID);
    }

    echo "</tbody></table>";

    echo "</div>";
}

function RenderArticleComment(
    int $articleID,
    ?string $user,
    string $comment,
    int $submittedDate,
    ?string $localUser,
    int $articleTypeID,
    int $commentID,
    bool $allowDelete,
): void {
    $class = '';
    $deleteIcon = '';

    if ($user && $user === $localUser || $allowDelete) {
        $img = Blade::render('<x-fas-xmark class="text-red-600 h-5 w-5" />');
        $deleteIcon = "<div style='float: right;'><a onclick=\"removeComment($articleTypeID, $articleID, $commentID); return false;\" href='#' aria-label='Delete comment' title='Delete comment'>$img</a></div>";
    }

    if ($user === 'Server') {
        $deleteIcon = null;
        $class .= ' system';
    }

    echo "<tr class='comment$class' id='comment_" . $commentID . "_highlight'>";

    $niceDate = date("j M Y ", $submittedDate);
    $niceDate .= date("H:i", $submittedDate);

    sanitize_outputs($user, $comment);
    $comment = nl2br($comment);

    echo "<td class='align-top py-2'>";

    echo <<<HTML
        <div class="relative">
            <div class="absolute h-px w-px left-0" style="top: -74px;" id="comment_$commentID"></div>
        </div>
    HTML;

    if ($user !== 'Server') {
        echo userAvatar($user, label: false);
    }
    echo "</td>";
    echo "<td class='w-full py-2' colspan='3'>";
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

function RenderCommentInputRow(string $user, int $articleTypeId, int $articleId): void
{
    sanitize_outputs($user, $formStr);
    $commentId = "art_{$articleTypeId}_{$articleId}";
    $csrfField = csrf_field();
    $avatar = media_asset("/UserPic/$user.png");

    echo <<<HTML
        <tr id="comment_$commentId" class='do-not-highlight'>
            <td class='align-top pb-3'>
                <img alt="$user" title="$user" class="badgeimg" src="$avatar" width="32" height="32">
            </td>
            <td class='w-full pb-3' colspan="3">
                <form action="/request/comment/create.php" method="post">
                    $csrfField
                    <input type="hidden" name="commentable_id" value="$articleId">
                    <input type="hidden" name="commentable_type" value="$articleTypeId">
                    <div x-data="{ isValid: true }" class="flex align-center mb-1">
                        <textarea
                            class="comment-textarea"
                            name="body"
                            maxlength="2000"
                            placeholder="Enter a comment here..."
                            id="comment_textarea_$commentId"
                            x-on:input="autoExpandTextInput(\$el); isValid = window.getStringByteCount(\$event.target.value) <= 2000;"
                        ></textarea>
                        <button class="btn h-9 ml-2" :disabled="!isValid" aria-label="Post comment" title="Post comment">
                            Submit
                        </button>
                    </div>
                    <div class="textarea-counter" data-textarea-id="comment_textarea_$commentId"></div>
                    <div class="text-danger hidden"></div>
                </form>
            </td>
        </tr>
    HTML;
}

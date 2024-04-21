<?php

use App\Community\Enums\ArticleType;
use App\Community\Enums\SubscriptionSubjectType;
use App\Enums\Permissions;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Blade;

function RenderCommentsComponent(
    ?string $username,
    int $numComments,
    array $commentData,
    int $articleID,
    int $articleTypeID,
    int $permissions,
    int $count = 20,
    int $offset = 0,
    bool $embedded = true
): void {
    $user = User::firstWhere('User', $username);

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
            $isSubscribed = isUserSubscribedToArticleComments($articleTypeID, $articleID, $user->id);
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

    // moderators can delete any comment
    // users can delete any comment off of their wall
    $canDeleteAnyComment =             
        $permissions >= Permissions::Moderator || 
        ($articleTypeID === ArticleType::User && $user?->id === $articleID);

    foreach ($commentData as $comment) {
        // users can delete their own comments
        $canDeleteComment = $canDeleteAnyComment || $comment['User'] === $username;

        echo Blade::render(
            '<x-comment.item
                :author="$author"
                :when="$when"
                :payload="$payload"
                :articleType="$articleType"
                :articleId="$articleId"
                :commentId="$commentId"
                :allowDelete="$allowDelete"
            />', [
                'author' => User::withTrashed()->firstWhere('User', $comment['User']),
                'when' => Carbon::createFromTimestamp($comment['Submitted']),
                'payload' => nl2br($comment['CommentPayload']),
                'articleType' => $articleTypeID,
                'articleId' => $articleID,
                'commentId' => $comment['ID'],
                'allowDelete' => $canDeleteComment,
            ]);
    }

    if (isset($user) && !$user->is_muted) {
        // User comment input:
        RenderCommentInputRow($user->User, $articleTypeID, $articleID);
    }

    echo "</tbody></table>";

    echo "</div>";

    if (isset($user) && $user->is_muted) {
        $mutedDate = getNiceDate($user->muted_until->timestamp);

        echo <<<HTML
            <div class="bg-embed p-2 rounded-b-lg">
                <p class="text-center text-muted">You are muted until $mutedDate.</p>
            </div>
        HTML;
    }
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

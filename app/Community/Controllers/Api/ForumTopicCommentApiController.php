<?php

declare(strict_types=1);

namespace App\Community\Controllers\Api;

use App\Community\Requests\UpsertForumTopicCommentRequest;
use App\Http\Controller;
use App\Models\ForumTopic;
use App\Models\ForumTopicComment;
use App\Models\User;
use App\Support\Shortcode\Shortcode;
use Illuminate\Http\JsonResponse;

class ForumTopicCommentApiController extends Controller
{
    public function store(
        UpsertForumTopicCommentRequest $request,
        ForumTopic $topic,
    ): JsonResponse {
        // The actual user making this API request.
        $requestingUser = $request->user();
        $sentByUser = null; // we'll keep this null unless it differs from $authorUser (someone posting as a team account)

        // The user that will publicly appear to everyone as the author.
        $authorUser = $requestingUser;

        if ($request->input('postAsUserId')) {
            $teamAccount = User::find($request->input('postAsUserId'));
            if ($teamAccount) {
                // Authorize posting as the team account.
                $this->authorize('create', [ForumTopicComment::class, $topic, $teamAccount]);

                $authorUser = $teamAccount;
                $sentByUser = $requestingUser; // track who actually sent it
            }
        } else {
            $this->authorize('create', [ForumTopicComment::class, $topic]);
        }

        // TODO use action
        $newComment = submitTopicComment(
            $authorUser,
            $topic->id,
            $topic->title,
            $request->input('body'),
            $sentByUser,
        );

        return response()->json(['success' => true, 'commentId' => $newComment->id]);
    }

    public function update(
        UpsertForumTopicCommentRequest $request,
        ForumTopicComment $comment,
    ): JsonResponse {
        $this->authorize('update', $comment);

        // The actual user making this API request.
        $requestingUser = $request->user();

        // Take any RA links and convert them to relevant shortcodes.
        // eg: "https://retroachievements.org/game/1" --> "[game=1]"
        $newPayload = normalize_shortcodes($request->input('body'));

        // Convert [user=$user->username] to [user=$user->id].
        $newPayload = Shortcode::convertUserShortcodesToUseIds($newPayload);

        // Convert [game={legacy_hub_id}] to [hub={game_set_id}].
        $newPayload = Shortcode::convertLegacyGameHubShortcodesToHubShortcodes($newPayload);

        $comment->body = $newPayload;

        // If this post is being edited by someone other than
        // the author, track who made the edit.
        if (!$requestingUser->is($comment->user)) {
            $comment->edited_by_id = $requestingUser->id;
        }

        $comment->save();

        return response()->json(['success' => true]);
    }

    public function destroy(): void
    {
    }
}

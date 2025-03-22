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
        ForumTopic $topic
    ): JsonResponse {
        $this->authorize('create', [ForumTopicComment::class, $topic]);

        /** @var User $user */
        $user = $request->user();

        // TODO use action
        $newComment = submitTopicComment($user, $topic->id, $topic->title, $request->input('body'));

        return response()->json(['success' => true, 'commentId' => $newComment->id]);
    }

    public function update(
        UpsertForumTopicCommentRequest $request,
        ForumTopicComment $comment
    ): JsonResponse {
        $this->authorize('update', $comment);

        // Take any RA links and convert them to relevant shortcodes.
        // eg: "https://retroachievements.org/game/1" --> "[game=1]"
        $newPayload = normalize_shortcodes($request->input('body'));

        // Convert [user=$user->username] to [user=$user->id].
        $newPayload = Shortcode::convertUserShortcodesToUseIds($newPayload);

        // Convert [game={legacy_hub_id}] to [hub={game_set_id}].
        $newPayload = Shortcode::convertLegacyGameHubShortcodesToHubShortcodes($newPayload);

        $comment->body = $newPayload;
        $comment->save();

        return response()->json(['success' => true]);
    }

    public function destroy(): void
    {
    }
}

<?php

declare(strict_types=1);

namespace App\Community\Controllers\Api;

use App\Community\Requests\UpdateForumTopicCommentRequest;
use App\Http\Controller;
use App\Models\ForumTopicComment;
use App\Support\Shortcode\Shortcode;
use Illuminate\Http\JsonResponse;

class ForumTopicCommentApiController extends Controller
{
    public function store(): void
    {
    }

    public function update(
        UpdateForumTopicCommentRequest $request,
        ForumTopicComment $comment
    ): JsonResponse {
        $this->authorize('update', $comment);

        // Take any RA links and convert them to relevant shortcodes.
        // eg: "https://retroachievements.org/game/1" --> "[game=1]"
        $newPayload = normalize_shortcodes($request->input('body'));

        // Convert [user=$user->username] to [user=$user->id].
        $newPayload = Shortcode::convertUserShortcodesToUseIds($newPayload);

        $comment->body = $newPayload;
        $comment->save();

        return response()->json(['success' => true]);
    }

    public function destroy(): void
    {
    }
}

<?php

declare(strict_types=1);

namespace App\Community\Controllers\Api;

use App\Community\Requests\StoreForumTopicRequest;
use App\Http\Controller;
use App\Models\Forum;
use App\Models\ForumCategory;
use App\Models\ForumTopic;
use Illuminate\Http\JsonResponse;

class ForumTopicApiController extends Controller
{
    public function store(
        ForumCategory $category,
        Forum $forum,
        StoreForumTopicRequest $request
    ): JsonResponse {
        $this->authorize('create', [ForumTopic::class, $forum]);

        $newForumTopicComment = submitNewTopic(
            $request->user(),
            $forum->id,
            $request->input('title'),
            $request->input('body'),
        );

        return response()->json([
            'success' => true,
            'newTopicId' => $newForumTopicComment->forumTopic->id,
        ]);
    }

    public function update(): void
    {
    }

    public function destroy(): void
    {
    }
}

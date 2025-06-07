<?php

declare(strict_types=1);

namespace App\Community\Controllers\Api;

use App\Community\Requests\GateForumTopicRequest;
use App\Community\Requests\StoreForumTopicRequest;
use App\Community\Requests\UpdateForumTopicRequest;
use App\Http\Controller;
use App\Models\Forum;
use App\Models\ForumCategory;
use App\Models\ForumTopic;
use App\Models\Game;
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

    public function update(
        ForumTopic $topic,
        UpdateForumTopicRequest $request
    ): JsonResponse {
        $this->authorize('update', $topic);

        $topic->title = $request->input('title');
        $topic->save();

        return response()->json(['success' => true]);
    }

    public function destroy(ForumTopic $topic): JsonResponse
    {
        $this->authorize('delete', $topic);

        $topic->delete();

        // if this was the official forum topic for a game, clear out the association
        // so another forum topic can be created - soft deletes don't cascade update
        // the field to null. this also creates an audit log entry on the game.
        foreach (Game::where('ForumTopicID', $topic->id)->get() as $game) {
            $game->ForumTopicID = null;
            $game->save();
        }

        return response()->json(['success' => true]);
    }

    public function gate(
        ForumTopic $topic,
        GateForumTopicRequest $request
    ): JsonResponse {
        $this->authorize('gate', $topic);

        $topic->required_permissions = $request->input('permissions');
        $topic->save();

        return response()->json(['success' => true]);
    }

    public function toggleLock(ForumTopic $topic): JsonResponse
    {
        $this->authorize('lock', $topic);

        // If the topic is already locked, unlock it.
        // Otherwise, lock it.
        if ($topic->is_locked) {
            $topic->locked_at = null;
        } else {
            $topic->locked_at = now();
        }

        $topic->save();

        return response()->json(['success' => true]);
    }
}

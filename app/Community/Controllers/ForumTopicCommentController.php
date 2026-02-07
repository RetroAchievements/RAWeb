<?php

declare(strict_types=1);

namespace App\Community\Controllers;

use App\Community\Actions\ReplaceBackingGameShortcodesWithGameUrlsAction;
use App\Community\Actions\ReplaceUserShortcodesWithUsernamesAction;
use App\Data\EditForumTopicCommentPagePropsData;
use App\Data\ForumTopicCommentData;
use App\Http\Controller;
use App\Models\ForumTopicComment;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class ForumTopicCommentController extends Controller
{
    public function show(ForumTopicComment $comment): RedirectResponse
    {
        abort_if($comment->trashed() || $comment->forumTopic === null, 404);

        $url = route('forum-topic.show', ['topic' => $comment->forumTopic, 'comment' => $comment->id]);

        return redirect("{$url}#{$comment->id}");
    }

    public function edit(ForumTopicComment $comment): InertiaResponse
    {
        $this->authorize('update', $comment);

        // "[user=1]" -> "[user=Scott]"
        $comment->body = (new ReplaceUserShortcodesWithUsernamesAction())->execute($comment->body);

        // "[game=backingGameId]" -> "https://retroachievements.org/game/1?set=9534"
        $comment->body = (new ReplaceBackingGameShortcodesWithGameUrlsAction())->execute($comment->body);

        $props = new EditForumTopicCommentPagePropsData(
            forumTopicComment: ForumTopicCommentData::from($comment)->include(
                'forumTopic',
                'forumTopic.forum',
                'forumTopic.forum.category',
            ),
        );

        return Inertia::render('forums/post/[comment]/edit', $props);
    }
}

<?php

declare(strict_types=1);

namespace App\Community\Livewire\Forms;

use App\Models\ForumTopic;
use App\Models\ForumTopicComment;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Validate;
use Livewire\Features\SupportRedirects\Redirector;
use Livewire\Form;

class ForumTopicCommentForm extends Form
{
    use AuthorizesRequests;

    #[Validate('required|max:60000')]
    public string $body = '';

    #[Locked]
    public ForumTopic $forumTopic;

    #[Locked]
    public ForumTopicComment $forumTopicComment;

    public function setForumTopic(ForumTopic $forumTopic): void
    {
        $this->forumTopic = $forumTopic;
    }

    public function setForumTopicComment(ForumTopicComment $forumTopicComment): void
    {
        $this->forumTopicComment = $forumTopicComment;
        $this->body = $forumTopicComment->body;
    }

    public function store(): RedirectResponse|Redirector
    {
        $this->authorize('create', [ForumTopicComment::class, $this->forumTopic]);
        $this->validate();

        $user = Auth::user();

        $newComment = submitTopicComment($user, $this->forumTopic->id, null, $this->body);

        $redirectUrl = route('forum.topic', [
            'forumTopic' => $this->forumTopic,
            'comment' => $newComment->id,
        ]);

        return redirect($redirectUrl)->with('success', __('legacy.success.send'));
    }

    public function update(): RedirectResponse|Redirector
    {
        $this->authorize('update', [ForumTopicComment::class, $this->forumTopicComment]);
        $this->validate();

        editTopicComment($this->forumTopicComment->id, $this->body);

        $commentId = $this->forumTopicComment->id;
        $topicId = $this->forumTopicComment->forum_topic_id;

        $redirectUrl = route('forum.topic', [
            'forumTopic' => $topicId,
            'commentId' => $commentId,
        ])
            . '#{$commentId}';

        return redirect($redirectUrl)->with('success', __('legacy.success.update'));
    }
}

<?php

declare(strict_types=1);

namespace App\Community\Livewire\Forms;

use App\Models\ForumTopicComment;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
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
    public ForumTopicComment $forumTopicComment;

    public function setForumTopicComment(ForumTopicComment $forumTopicComment): void
    {
        $this->forumTopicComment = $forumTopicComment;
        $this->body = $forumTopicComment->body;
    }

    public function update(): Redirector
    {
        $this->authorize('update', [ForumTopicComment::class, $this->forumTopicComment]);
        $this->validate();

        editTopicComment($this->forumTopicComment->id, $this->body);

        $commentId = $this->forumTopicComment->id;
        $topicId = $this->forumTopicComment->forum_topic_id;

        return redirect(url("/viewtopic.php?t=$topicId&c=$commentId#$commentId"))
            ->with('success', __('legacy.success.update'));
    }
}

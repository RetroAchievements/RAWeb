<?php

declare(strict_types=1);

namespace App\Community\Livewire\Forms;

use App\Community\Actions\ReplaceUserShortcodesWithUsernamesAction;
use App\Models\ForumTopicComment;
use App\Support\Rules\ContainsRegularCharacter;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Livewire\Attributes\Locked;
use Livewire\Features\SupportRedirects\Redirector;
use Livewire\Form;

class ForumTopicCommentForm extends Form
{
    use AuthorizesRequests;

    public string $body = '';

    #[Locked]
    public ForumTopicComment $forumTopicComment;

    public function setForumTopicComment(ForumTopicComment $forumTopicComment): void
    {
        $this->forumTopicComment = $forumTopicComment;

        // "[user=1]" -> "[user=Scott]"
        $this->body = (
            new ReplaceUserShortcodesWithUsernamesAction()
        )->execute($this->forumTopicComment->body);
    }

    public function update(): RedirectResponse|Redirector
    {
        $this->authorize('update', [ForumTopicComment::class, $this->forumTopicComment]);
        $this->validate([
            'body' => [
                'required',
                'string',
                'max:60000',
                new ContainsRegularCharacter(),
            ],
        ]);

        editTopicComment($this->forumTopicComment->id, $this->body);

        $commentId = $this->forumTopicComment->id;
        $topicId = $this->forumTopicComment->forum_topic_id;

        return redirect(url("/viewtopic.php?t=$topicId&c=$commentId#$commentId"))
            ->with('success', __('legacy.success.update'));
    }
}

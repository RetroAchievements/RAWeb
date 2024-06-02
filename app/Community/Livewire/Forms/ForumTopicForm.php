<?php

declare(strict_types=1);

namespace App\Community\Livewire\Forms;

use App\Models\Forum;
use App\Models\ForumTopic;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Validate;
use Livewire\Features\SupportRedirects\Redirector;
use Livewire\Form;

class ForumTopicForm extends Form
{
    use AuthorizesRequests;

    #[Validate('required|min:2|max:255')]
    public string $title = '';

    #[Validate('required|max:60000')]
    public string $body = '';

    #[Locked]
    public Forum $forum;

    public function setForum(Forum $forum): void
    {
        $this->forum = $forum;
    }

    public function store(): Redirector
    {
        $this->authorize('create', [ForumTopic::class, $this->forum]);
        $this->validate();

        $user = Auth::user();

        $newForumTopicComment = submitNewTopic(
            $user,
            $this->forum->id,
            $this->title,
            $this->body,
        );

        return redirect(url("/viewtopic.php?t={$newForumTopicComment->forumTopic->id}"))
            ->with('success', __('legacy.success.create'));
    }
}

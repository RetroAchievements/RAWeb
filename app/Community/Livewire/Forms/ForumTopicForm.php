<?php

declare(strict_types=1);

namespace App\Community\Livewire\Forms;

use App\Models\Forum;
use App\Models\ForumTopic;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
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

    public int $requiredPermissions = 0;

    #[Locked]
    public Forum $forum;

    #[Locked]
    public ForumTopic $forumTopic;

    public function setForum(Forum $forum): void
    {
        $this->forum = $forum;
    }

    public function setForumTopic(ForumTopic $forumTopic): void
    {
        $this->forumTopic = $forumTopic;

        $this->title = $this->forumTopic->title;
        $this->requiredPermissions = $this->forumTopic->RequiredPermissions;
    }

    public function store(): RedirectResponse|Redirector
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

        return redirect(route('forum.topic', ['forumTopic' => $newForumTopicComment->forumTopic->id]))
            ->with('success', __('legacy.success.create'));
    }

    public function delete(): RedirectResponse|Redirector
    {
        $this->authorize('delete', $this->forumTopic);

        $forumId = $this->forumTopic->forum->id;

        $this->forumTopic->delete();

        return redirect(url("/viewforum.php?f={$forumId}"))
            ->with('success', __('legacy.success.delete'));
    }

    public function updateTitle(): RedirectResponse|Redirector
    {
        $this->authorize('update', $this->forumTopic);
        $validated = $this->validateOnly('title');

        $this->forumTopic->title = $validated['title'];
        $this->forumTopic->save();

        return redirect(route('forum.topic', ['forumTopic' => $this->forumTopic]))
            ->with('success', __('legacy.success.modify'));
    }

    public function updateRequiredPermissions(): RedirectResponse|Redirector
    {
        $this->authorize('manage', $this->forumTopic);

        $this->forumTopic->RequiredPermissions = $this->requiredPermissions;
        $this->forumTopic->save();

        return redirect(route('forum.topic', ['forumTopic' => $this->forumTopic]))
            ->with('success', __('legacy.success.modify'));
    }
}

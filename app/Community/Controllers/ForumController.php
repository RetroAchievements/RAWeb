<?php

declare(strict_types=1);

namespace App\Community\Controllers;

use App\Community\Requests\ForumRequest;
use App\Models\Forum;
use App\Models\ForumCategory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ForumController extends \App\Http\Controller
{
    public function index(): void
    {
        $this->authorize('viewAny', Forum::class);
    }

    public function create(ForumCategory $forumCategory): void
    {
        $this->authorize('store', [Forum::class, $forumCategory]);
    }

    public function store(Request $request, ForumCategory $forumCategory): void
    {
        $this->authorize('store', [Forum::class, $forumCategory]);
    }

    public function show(Forum $forum, ?string $slug = null): View|RedirectResponse
    {
        $this->authorize('view', $forum);

        if (!$this->resolvesToSlug($forum->slug, $slug)) {
            return redirect($forum->canonicalUrl);
        }

        $forum->withCount('topics');

        $topics = $forum->topics()
            ->withCount('comments')
            ->with('latestComment')
            ->orderbyLatestActivity('desc')
            ->paginate();

        return view('forum.show')
            ->with('category', $forum->category)
            ->with('topics', $topics)
            ->with('forum', $forum);
    }

    public function edit(Forum $forum): View
    {
        $this->authorize('update', $forum);

        return view('forum.edit')->with('forum', $forum);
    }

    public function update(ForumRequest $request, Forum $forum): RedirectResponse
    {
        $this->authorize('update', $forum);

        $forum->fill($request->validated())->save();

        return back()->with('success', $this->resourceActionSuccessMessage('forum', 'update'));
    }

    public function destroy(Forum $forum): void
    {
        $this->authorize('delete', $forum);

        dd('implement');
    }
}

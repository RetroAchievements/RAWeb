<?php

declare(strict_types=1);

namespace App\Community\Controllers;

use App\Community\Models\Forum;
use App\Community\Models\ForumTopic;
use App\Community\Requests\ForumTopicRequest;
use App\Support\Shortcode\ShortcodeModelCollector;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ForumTopicController extends \App\Http\Controller
{
    public function index(): void
    {
        $this->authorize('viewAny', ForumTopic::class);
    }

    public function create(Forum $forum): View
    {
        $this->authorize('create', [ForumTopic::class, $forum]);

        return view('forum-topic.create')
            ->with('forum', $forum);
    }

    public function store(ForumTopicRequest $request, Forum $forum): RedirectResponse
    {
        $this->authorize('create', [ForumTopic::class, $forum]);

        $input = collect($request->validated());

        /** @var ForumTopic $forumTopic */
        $forumTopic = new ForumTopic($input->toArray());
        $forumTopic->user_id = $request->user()->id;
        $forumTopic = $forum->topics()->save($forumTopic);

        return redirect(route('forum-topic.show', $forumTopic))
            ->with('success', $this->resourceActionSuccessMessage('forum-topic', 'create'));
    }

    public function show(Request $request, ForumTopic $topic, ?string $slug = null): View|RedirectResponse
    {
        $this->authorize('view', $topic);

        if (!$this->resolvesToSlug($topic->slug, $slug)) {
            return redirect($topic->canonicalUrl);
        }

        /*
         * eager load models referenced in content
         */
        ShortcodeModelCollector::collect($topic->body);

        $topic->load([
            'forum',
            'user',
        /*
         * eager loading won't let us have a grid paginator for a morphed relationship
         */
            // 'comments' => function($query){
            //     $query->sort('created_at', 'asc')->paginate();
            // },
        ]);

        $topic->withCount([
            'comments',
        ]);

        return view('forum-topic.show')
            ->with('topic', $topic)
            ->with('highlightCommentId', $request->input('highlight'));
    }

    public function edit(ForumTopic $topic): View
    {
        $this->authorize('update', $topic);

        return view('forum-topic.edit')
            ->with(['topic' => $topic]);
    }

    public function update(ForumTopicRequest $request, ForumTopic $topic): RedirectResponse
    {
        $this->authorize('update', $topic);

        $topic->fill($request->validated())->save();

        return redirect($topic->canonicalUrl)
            ->with('success', $this->resourceActionSuccessMessage('forum-topic', 'update'));
    }

    public function destroy(ForumTopic $topic): void
    {
        $this->authorize('delete', $topic);
    }
}

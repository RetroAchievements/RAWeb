<?php

declare(strict_types=1);

namespace App\Community\Controllers;

use App\Community\Requests\ForumTopicRequest;
use App\Data\ForumTopicData;
use App\Data\PaginatedData;
use App\Models\Forum;
use App\Models\ForumTopic;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

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

        $input = (new Collection($request->validated()));

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

    public function recentlyActive(): InertiaResponse
    {
        $recentlyActiveTopics = ForumTopic::query()
            ->select('ForumTopic.*', 'ftc24h.comment_count as comment_count_24h', 'ftc24h.oldest_comment_id as oldest_comment_id_24h', 'ftc7d.comment_count as comment_count_7d', 'ftc7d.oldest_comment_id as oldest_comment_id_7d')
            ->with(['latestComment.user'])
            ->leftJoinSub($this->buildCommentCountSubQuery('24 HOUR'), 'ftc24h', 'ForumTopic.ID', '=', 'ftc24h.ForumTopicID')
            ->leftJoinSub($this->buildCommentCountSubQuery('7 DAY'), 'ftc7d', 'ForumTopic.ID', '=', 'ftc7d.ForumTopicID')
            ->orderByDesc(
                DB::raw('(SELECT DateCreated FROM ForumTopicComment WHERE ForumTopicComment.ForumTopicID = ForumTopic.ID AND ForumTopicComment.Authorised = 1 ORDER BY DateCreated DESC LIMIT 1)')
            )
            ->paginate(25);

        $transformedTopics = $recentlyActiveTopics->getCollection()->map(function (ForumTopic $topic) {
            return ForumTopicData::fromRecentlyActiveTopic($topic)->include(
                'commentCount24h',
                'oldestComment24hId',
                'commentCount7d',
                'oldestComment7dId',
            );
        });

        $recentlyActiveTopics->setCollection($transformedTopics);

        return Inertia::render('forums/recent-posts', [
            'paginatedTopics' => PaginatedData::fromLengthAwarePaginator($recentlyActiveTopics),
        ]);
    }

    private function buildCommentCountSubQuery(string $interval): Builder
    {
        return DB::table('ForumTopicComment')
            ->select('ForumTopicID', DB::raw('COUNT(*) as comment_count'), DB::raw('MIN(ID) as oldest_comment_id'))
            ->where('Authorised', '=', 1)
            ->where('DateCreated', '>=', DB::raw("DATE_SUB(NOW(), INTERVAL $interval)"))
            ->groupBy('ForumTopicID');
    }
}

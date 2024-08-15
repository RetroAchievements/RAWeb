<?php

declare(strict_types=1);

namespace App\Community\Controllers;

use App\Community\Requests\ForumTopicRequest;
use App\Data\ForumTopicData;
use App\Data\PaginatedData;
use App\Enums\Permissions;
use App\Models\Forum;
use App\Models\ForumTopic;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
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

    private function getTotalRecentForumTopics(int $permissions = Permissions::Unregistered): int
    {
        return ForumTopic::query()
            ->distinct('ForumTopic.ID')
            ->leftJoin('Forum', 'Forum.ID', '=', 'ForumTopic.ForumID')
            ->leftJoin('ForumTopicComment', function ($join) {
                $join->on('ForumTopicComment.ForumTopicID', '=', 'ForumTopic.ID')
                    ->where('ForumTopicComment.Authorised', '=', 1);
            })
            ->where('ForumTopic.RequiredPermissions', '<=', $permissions)
            ->whereNull('ForumTopic.deleted_at')
            ->count('ForumTopic.ID');
    }

    public function recentlyActive(Request $request): InertiaResponse
    {
        $offset = $request->input('page', 1) - 1;
        $count = 25;

        /** @var User $user */
        $user = auth()->user();
        $permissions = Permissions::Unregistered;
        if ($user) {
            $permissions = (int) $user->getAttribute('Permissions');
        }

        $topics = $this->getRecentForumTopics(
            page: $request->input('page', 1),
            permissions: $permissions,
        );

        $transformedTopics = array_map(
            fn ($topic) => ForumTopicData::fromRecentlyActiveTopic($topic),
            $topics
        );

        $paginator = new LengthAwarePaginator(
            items: $transformedTopics,
            total: $this->getTotalRecentForumTopics($permissions),
            perPage: $count,
            currentPage: $offset + 1,
            options: [
                'path' => $request->url(),
                'query' => $request->query(),
            ],
        );

        $paginatedTopics = PaginatedData::fromLengthAwarePaginator($paginator);

        return Inertia::render('forums/recent-posts', [
            'paginatedTopics' => $paginatedTopics,
        ]);
    }

    private function getRecentForumTopics(int $page = 1, int $permissions = Permissions::Unregistered): array
    {
        $count = 25;
        $offset = ($page - 1) * $count;

        $query = "
            SELECT ft.ID as ForumTopicID, ft.Title as ForumTopicTitle,
                   f.ID as ForumID, f.Title as ForumTitle,
                   lc.CommentID, lftc.DateCreated as PostedAt, lftc.author_id,
                   ua.User AS Author, ua.display_name AS AuthorDisplayName,
                   LEFT(lftc.Payload, 90) AS ShortMsg,
                   LENGTH(lftc.Payload) > 90 AS IsTruncated,
                   d1.CommentID as CommentID_1d, d1.Count as Count_1d,
                   d7.CommentID as CommentID_7d, d7.Count as Count_7d
            FROM ForumTopic AS ft
            LEFT JOIN Forum AS f on f.ID = ft.ForumID
            LEFT JOIN (
                SELECT ftc.ForumTopicId, MAX(ftc.ID) as CommentID
                FROM ForumTopicComment ftc
                WHERE ftc.Authorised=1
                GROUP BY ftc.ForumTopicId
            ) AS lc ON lc.ForumTopicId = ft.ID
            LEFT JOIN ForumTopicComment AS lftc ON lftc.ID = lc.CommentID
            LEFT JOIN (
                SELECT ftc.ForumTopicId, MIN(ftc.ID) as CommentID, COUNT(ftc.ID) as Count
                FROM ForumTopicComment ftc
                WHERE ftc.Authorised=1 AND DateCreated >= DATE_SUB(NOW(), INTERVAL 1 DAY)
                GROUP BY ftc.ForumTopicId
            ) AS d1 ON d1.ForumTopicId = ft.ID
            LEFT JOIN (
                SELECT ftc.ForumTopicId, MIN(ftc.ID) as CommentID, COUNT(ftc.ID) as Count
                FROM ForumTopicComment ftc
                WHERE ftc.Authorised=1 AND DateCreated >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                GROUP BY ftc.ForumTopicId
            ) AS d7 ON d7.ForumTopicId = ft.ID
            LEFT JOIN UserAccounts AS ua ON ua.ID = lftc.author_id
            WHERE ft.RequiredPermissions <= :permissions AND ft.deleted_at IS NULL
            ORDER BY PostedAt DESC
            LIMIT :offset, :count";

        return legacyDbFetchAll($query, [
            'offset' => $offset,
            'count' => $count,
            'permissions' => $permissions,
        ])->toArray();
    }
}

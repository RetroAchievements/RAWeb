<?php

declare(strict_types=1);

namespace App\Community\Controllers;

use App\Community\Actions\BuildAggregateRecentForumPostsDataAction;
use App\Community\Data\RecentPostsPagePropsData;
use App\Community\Requests\ForumTopicRequest;
use App\Data\CreateForumTopicPagePropsData;
use App\Data\ForumData;
use App\Enums\Permissions;
use App\Http\Controller;
use App\Models\Forum;
use App\Models\ForumCategory;
use App\Models\ForumTopic;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class ForumTopicController extends Controller
{
    public function index(): void
    {
        $this->authorize('viewAny', ForumTopic::class);
    }

    public function create(ForumCategory $category, Forum $forum, Request $request): InertiaResponse
    {
        $this->authorize('create', [ForumTopic::class, $forum]);

        $props = new CreateForumTopicPagePropsData(
            forum: ForumData::from($forum)->include(
                'category'
            ),
        );

        return Inertia::render('forums/[category]/[forum]/create', $props);
    }

    public function show(Request $request, ForumTopic $topic, ?string $slug = null): void
    {
    }

    public function update(ForumTopicRequest $request, ForumTopic $topic): void
    {
    }

    public function destroy(ForumTopic $topic): void
    {
    }

    public function recentPosts(
        Request $request,
        BuildAggregateRecentForumPostsDataAction $buildAggregateRecentPostsData
    ): InertiaResponse {
        /** @var ?User $user */
        $user = Auth::user();
        $permissions = $user ? (int) $user->getAttribute('Permissions') : Permissions::Unregistered;

        $paginatedTopics = $buildAggregateRecentPostsData->execute(
            permissions: $permissions,
            page: (int) $request->input('page', 1),
            limit: 25,
            paginationPath: $request->url(),
            paginationQuery: $request->query(),
        );

        $props = new RecentPostsPagePropsData($paginatedTopics);

        return Inertia::render('forums/recent-posts', $props);
    }
}

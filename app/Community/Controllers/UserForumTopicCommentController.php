<?php

declare(strict_types=1);

namespace App\Community\Controllers;

use App\Community\Data\UserRecentPostsPagePropsData;
use App\Data\ForumTopicData;
use App\Data\PaginatedData;
use App\Data\UserData;
use App\Enums\Permissions;
use App\Http\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

// TODO refactor: build page props using an action (see HomeController)

class UserForumTopicCommentController extends Controller
{
    public function index(Request $request, User $user): InertiaResponse
    {
        $this->authorize('view', $user);

        $offset = $request->input('page', 1) - 1;
        $count = 25;

        /** @var User $me */
        $me = $request->user();
        $permissions = Permissions::Unregistered;
        if ($me) {
            $permissions = (int) $me->getAttribute('Permissions');
        }

        $posts = $this->getUserPosts(
            $user,
            page: (int) $request->input('page', 1),
            permissions: $permissions,
        );

        $transformedPosts = array_map(
            fn ($post) => ForumTopicData::fromUserPost($post)->include('latestComment'),
            $posts
        );

        $paginator = new LengthAwarePaginator(
            items: $transformedPosts,
            total: $user->forumPosts()->authorized()->viewable($me)->count(),
            perPage: $count,
            currentPage: $offset + 1,
            options: [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );

        $paginatedPosts = PaginatedData::fromLengthAwarePaginator($paginator);

        $props = new UserRecentPostsPagePropsData(
            UserData::fromUser($user),
            $paginatedPosts
        );

        return Inertia::render('user/[user]/posts', $props);
    }

    private function getUserPosts(User $user, int $page = 1, int $permissions = Permissions::Unregistered): array
    {
        $count = 25;
        $offset = ($page - 1) * $count;

        $query = "
            SELECT 
                ft.id AS ForumTopicID, 
                ft.title AS ForumTopicTitle,
                f.id AS ForumID, 
                f.title AS ForumTitle,
                lftc.id AS CommentID, 
                lftc.created_at AS PostedAt, 
                lftc.author_id,
                ua.User AS Author, 
                ua.display_name AS AuthorDisplayName,
                LEFT(lftc.body, 260) AS ShortMsg,
                LENGTH(lftc.body) > 260 AS IsTruncated
            FROM forum_topic_comments AS lftc
            INNER JOIN forum_topics AS ft ON ft.id = lftc.forum_topic_id
            INNER JOIN forums AS f ON f.id = ft.forum_id
            LEFT JOIN UserAccounts AS ua ON ua.ID = lftc.author_id
            WHERE lftc.author_id = :author_id 
              AND lftc.is_authorized = 1
              AND ft.required_permissions <= :permissions 
              AND ft.deleted_at IS NULL
            ORDER BY lftc.created_at DESC
            LIMIT :offset, :count";

        return legacyDbFetchAll($query, [
            'author_id' => $user->id,
            'offset' => $offset,
            'count' => $count,
            'permissions' => $permissions,
        ])->toArray();
    }
}

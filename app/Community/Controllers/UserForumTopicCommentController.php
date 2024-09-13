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

class UserForumTopicCommentController extends Controller
{
    public function index(Request $request, User $user): InertiaResponse
    {
        $this->authorize('view', $user);

        $offset = $request->input('page', 1) - 1;
        $count = 25;

        /** @var User $me */
        $me = auth()->user();
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
                ft.ID AS ForumTopicID, 
                ft.Title AS ForumTopicTitle,
                f.ID AS ForumID, 
                f.Title AS ForumTitle,
                lftc.ID AS CommentID, 
                lftc.DateCreated AS PostedAt, 
                lftc.author_id,
                ua.User AS Author, 
                ua.display_name AS AuthorDisplayName,
                LEFT(lftc.Payload, 260) AS ShortMsg,
                LENGTH(lftc.Payload) > 260 AS IsTruncated
            FROM ForumTopicComment AS lftc
            INNER JOIN ForumTopic AS ft ON ft.ID = lftc.ForumTopicID
            INNER JOIN Forum AS f ON f.ID = ft.ForumID
            LEFT JOIN UserAccounts AS ua ON ua.ID = lftc.author_id
            WHERE lftc.author_id = :author_id 
              AND lftc.Authorised = 1
              AND ft.RequiredPermissions <= :permissions 
              AND ft.deleted_at IS NULL
            ORDER BY lftc.DateCreated DESC
            LIMIT :offset, :count";

        return legacyDbFetchAll($query, [
            'author_id' => $user->id,
            'offset' => $offset,
            'count' => $count,
            'permissions' => $permissions,
        ])->toArray();
    }
}

<?php

declare(strict_types=1);

namespace App\Community\Controllers;

use App\Community\Data\UserRecentPostsPagePropsData;
use App\Data\ForumTopicData;
use App\Data\PaginatedData;
use App\Data\UserData;
use App\Enums\Permissions;
use App\Http\Controller;
use App\Models\ForumTopicComment;
use App\Models\User;
use App\Support\Shortcode\Shortcode;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class UserForumTopicCommentController extends Controller
{
    private const POSTS_PER_PAGE = 25;

    public function index(Request $request, User $user): InertiaResponse
    {
        $this->authorize('view', $user);

        $page = (int) $request->input('page', 1);

        /** @var User $me */
        $me = $request->user();
        $permissions = Permissions::Unregistered;
        if ($me) {
            $permissions = (int) $me->getAttribute('Permissions');
        }

        $postsQuery = $this->getUserPostsQuery($user, $permissions);

        $posts = $this->getUserPosts(
            clone $postsQuery,
            page: $page,
        );

        $shortcodeIds = [];
        foreach ($posts as $post) {
            $postShortcodeIds = Shortcode::extractShortcodeIds($post['ShortMsg']);
            foreach ($postShortcodeIds as $key => $ids) {
                $shortcodeIds[$key] = array_merge($shortcodeIds[$key] ?? [], $ids);
            }
        }
        $shortcodeRecords = Shortcode::fetchRecords($shortcodeIds);

        $transformedPosts = array_map(
            fn ($post) => ForumTopicData::fromUserPost($post, $shortcodeRecords)->include('latestComment'),
            $posts
        );

        $paginator = new LengthAwarePaginator(
            items: $transformedPosts,
            total: $postsQuery->count(),
            perPage: self::POSTS_PER_PAGE,
            currentPage: $page,
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

    private function getUserPosts(Builder $query, int $page = 1): array
    {
        return $query
            ->select([
                'ft.id as ForumTopicID',
                'ft.title as ForumTopicTitle',
                'f.id as ForumID',
                'f.title as ForumTitle',
                'forum_topic_comments.id as CommentID',
                'forum_topic_comments.created_at as PostedAt',
                'forum_topic_comments.author_id',
                'ua.username as Author',
                'ua.display_name as AuthorDisplayName',
                'forum_topic_comments.body as ShortMsg',
            ])
            ->selectRaw('0 as IsTruncated')
            ->orderByDesc('forum_topic_comments.created_at')
            ->forPage($page, self::POSTS_PER_PAGE)
            ->get()
            ->map(fn (object $post) => (array) $post)
            ->all();
    }

    private function getUserPostsQuery(User $user, int $permissions = Permissions::Unregistered): Builder
    {
        return ForumTopicComment::query()
            ->authorized()
            ->where('forum_topic_comments.author_id', $user->id)
            ->toBase()
            ->join('forum_topics as ft', 'ft.id', '=', 'forum_topic_comments.forum_topic_id')
            ->join('forums as f', 'f.id', '=', 'ft.forum_id')
            ->leftJoin('users as ua', 'ua.id', '=', 'forum_topic_comments.author_id')
            ->where('ft.required_permissions', '<=', $permissions)
            ->whereNull('ft.deleted_at');
    }
}

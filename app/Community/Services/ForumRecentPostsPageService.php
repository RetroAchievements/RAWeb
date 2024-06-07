<?php

declare(strict_types=1);

namespace App\Community\Services;

use App\Enums\Permissions;
use App\Models\User;

class ForumRecentPostsPageService
{
    public function buildViewData(
        ?User $currentUser,
        int $currentOffset = 0,
        ?User $targetUser = null
    ): array {
        $maxPerPage = 25;

        $recentForumPosts = $this->buildPostsList(
            $currentUser,
            $currentOffset,
            $maxPerPage,
            $targetUser,
        );

        [$previousPageUrl, $nextPageUrl] = $this->buildPaginationUrls(
            $maxPerPage,
            $currentOffset,
            count($recentForumPosts),
            $targetUser,
        );

        return [
            'maxPerPage' => $maxPerPage,
            'nextPageUrl' => $nextPageUrl,
            'previousPageUrl' => $previousPageUrl,
            'recentForumPosts' => $recentForumPosts,
            'targetUser' => $targetUser,
        ];
    }

    private function buildPaginationUrls(
        int $maxPerPage,
        int $currentOffset,
        int $recentForumPostsCount,
        ?User $targetUser = null,
    ): array {
        $previousPageUrl = null;
        $nextPageUrl = null;

        $routeName = $targetUser ? 'user.posts' : 'forum.recent-posts';
        $routeArgs = [];
        if ($targetUser) {
            $routeArgs['user'] = $targetUser;
        }

        if ($currentOffset > 0) {
            // Don't let a crawler try to index a URL with "?offset=0".
            if ($currentOffset === $maxPerPage) {
                $previousPageUrl = route($routeName, $routeArgs);
            } else {
                $previousPageUrl = route($routeName, [
                    ...$routeArgs,
                    'offset' => $currentOffset - $maxPerPage,
                ]);
            }
        }
        if ($recentForumPostsCount === $maxPerPage) {
            $nextPageUrl = route($routeName, [
                ...$routeArgs,
                'offset' => $currentOffset + $maxPerPage,
            ]);
        }

        return [$previousPageUrl, $nextPageUrl];
    }

    private function buildPostsList(
        ?User $currentUser = null,
        int $currentOffset = 0,
        int $maxPerPage = 25,
        ?User $targetUser = null,
    ): array {
        $postsList = [];

        $currentUserPermissions = $currentUser?->getAttribute('Permissions') ?? Permissions::Unregistered;

        if ($targetUser) {
            $postsList = getRecentForumPosts(
                $currentOffset,
                $maxPerPage,
                260,
                $currentUserPermissions,
                $targetUser->id,
            )
                ->toArray();
        } else {
            $postsList = getRecentForumTopics(
                $currentOffset,
                $maxPerPage,
                $currentUserPermissions,
                260,
            );
        }

        return $postsList;
    }
}

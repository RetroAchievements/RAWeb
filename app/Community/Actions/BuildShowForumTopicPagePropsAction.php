<?php

declare(strict_types=1);

namespace App\Community\Actions;

use App\Data\ForumTopicCommentData;
use App\Data\ForumTopicData;
use App\Data\PaginatedData;
use App\Data\ShowForumTopicPagePropsData;
use App\Data\UserPermissionsData;
use App\Models\ForumTopic;
use App\Models\User;
use App\Support\Shortcode\Shortcode;

class BuildShowForumTopicPagePropsAction
{
    /**
     * @return array{props: ?ShowForumTopicPagePropsData, redirectToPage: ?int}
     */
    public function execute(
        ForumTopic $topic,
        ?User $user,
        int $currentPage,
        int $perPage = 15,
    ): array {
        $paginatedForumTopicComments = $topic->visibleComments()
            ->orderBy('created_at')
            ->paginate($perPage, ['*'], 'page', $currentPage);

        $totalForumTopicComments = $paginatedForumTopicComments->total();
        $lastPage = (int) ceil($totalForumTopicComments / $perPage);

        // Constrain the current page between 1 and the last page.
        if ($currentPage !== 1 && $currentPage > $lastPage) {
            return ['props' => null, 'redirectToPage' => $lastPage];
        }

        // Extract the post bodies for processing before they're sent to the UI.
        $postBodies = $paginatedForumTopicComments->getCollection()->pluck('body')->all();

        // Convert user ID shortcodes to use display names.
        $updatedBodies = (new ConvertUserShortcodesFromIdsToDisplayNamesAction())->execute($postBodies);

        // Extract all dynamic entities from the updated bodies.
        $entities = (new ExtractDynamicShortcodeEntitiesAction())->execute($updatedBodies);

        // Fetch all dynamic content so it can be performantly hydrated in the UI.
        $dynamicEntities = (new FetchDynamicShortcodeContentAction())->execute(
            usernames: $entities['usernames'],
            ticketIds: $entities['ticketIds'],
            achievementIds: $entities['achievementIds'],
            gameIds: $entities['gameIds'],
            eventIds: $entities['eventIds'],
            hubIds: $entities['hubIds'],
        );

        // Finally, update the message bodies sent to the UI with the converted user shortcodes.
        $forumTopicComments = $paginatedForumTopicComments->getCollection()->map(function ($comment, $index) use ($updatedBodies) {
            $comment->body = $updatedBodies[$index];

            return ForumTopicCommentData::from($comment)->include(
                'user.createdAt',
                'user.deletedAt',
                'user.visibleRole',
            );
        })->all();

        $props = new ShowForumTopicPagePropsData(
            can: UserPermissionsData::fromUser($user, forumTopic: $topic)->include(
                'authorizeForumTopicComments',
                'createForumTopicComments',
                'deleteForumTopic',
                'lockForumTopic',
                'manageForumTopicComments',
                'manageForumTopics',
                'updateForumTopic',
            ),
            dynamicEntities: $dynamicEntities,
            forumTopic: ForumTopicData::from($topic)->include(
                'forum',
                'forum.category',
                'lockedAt',
                'requiredPermissions',
            ),
            isSubscribed: $user ? isUserSubscribedToForumTopic($topic->id, $user->id) : false,
            paginatedForumTopicComments: PaginatedData::fromLengthAwarePaginator(
                $paginatedForumTopicComments,
                total: $paginatedForumTopicComments->total(),
                items: $forumTopicComments
            ),
            metaDescription: Shortcode::stripAndClamp($updatedBodies[0], 220),
        );

        return ['props' => $props, 'redirectToPage' => null];
    }
}

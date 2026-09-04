<?php

declare(strict_types=1);

namespace App\Community\Actions;

use App\Community\Enums\SubscriptionSubjectType;
use App\Community\Services\SubscriptionService;
use App\Data\ForumTopicCommentData;
use App\Data\ForumTopicData;
use App\Data\PaginatedData;
use App\Data\ShowForumTopicPagePropsData;
use App\Data\UserData;
use App\Data\UserPermissionsData;
use App\Models\ForumTopic;
use App\Models\ForumTopicComment;
use App\Models\User;
use App\Policies\ForumTopicCommentPolicy;
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
        int $perPage = ForumTopic::COMMENTS_PER_PAGE,
        ?int $selectedCommentId = null,
    ): array {
        $paginatedForumTopicComments = $topic->visibleComments()
            ->with(['sentBy', 'editedBy'])
            ->orderBy('created_at')
            ->paginate($perPage, ['*'], 'page', $currentPage);

        abort_if($paginatedForumTopicComments->total() === 0, 404);

        $totalForumTopicComments = $paginatedForumTopicComments->total();
        $lastPage = (int) ceil($totalForumTopicComments / $perPage);

        // Constrain the current page between 1 and the last page.
        if ($currentPage !== 1 && $currentPage > $lastPage) {
            return ['props' => null, 'redirectToPage' => $lastPage];
        }

        $maskedAuthorIds = (new GetMaskedForumAuthorIdsAction())->execute($user);
        $canRevealMaskedPosts = $user && (new ForumTopicCommentPolicy())->manage($user);
        $comments = $paginatedForumTopicComments->getCollection()->values();

        // Extract the post bodies for processing before they're sent to the UI.
        $postBodies = $comments->pluck('body')->all();

        if (!$canRevealMaskedPosts) {
            foreach ($comments as $index => $comment) {
                if (in_array($comment->author_id, $maskedAuthorIds, true)) {
                    $postBodies[$index] = '';
                }
            }
        }

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

        $accessibleTeamAccounts = null;
        $replyableTeamAccounts = null;
        $accessibleTeamIds = [];
        if ($user) {
            $accessibleTeamIds = (new ForumTopicCommentPolicy())->getAccessibleTeamIds($user);
            if (!empty($accessibleTeamIds)) {
                $teamUsers = User::whereIn('id', $accessibleTeamIds)->with('roles')->get();
                $accessibleTeamAccounts = $teamUsers->map(fn ($teamUser) => UserData::fromUser($teamUser)->include('id'));

                $replyEligibleTeamUsers = $teamUsers->filter(
                    fn (User $teamUser) => $user->can('create', [ForumTopicComment::class, $topic, $teamUser]),
                );
                $replyableTeamAccounts = $replyEligibleTeamUsers->isNotEmpty()
                    ? $replyEligibleTeamUsers->values()->map(fn ($teamUser) => UserData::fromUser($teamUser)->include('id'))
                    : null;
            }
        }

        // Finally, update the message bodies sent to the UI with the converted user shortcodes.
        $forumTopicComments = $comments->map(
            function ($comment, $index) use ($updatedBodies, $user, $accessibleTeamIds, $maskedAuthorIds, $canRevealMaskedPosts) {
                $comment->body = $updatedBodies[$index];

                $includes = [
                    'user.createdAt',
                    'user.deletedAt',
                    'user.isBanned',
                    'user.isMuted',
                    'user.visibleRole',
                ];

                /**
                 * Include the sentBy and editedBy values if:
                 * A. The user is viewing a team account post they have access to, OR
                 * B. The user can manage forum posts (they can moderate the forum).
                 * If we always naively include it, Inertia will leak the value into the DOM.
                 */
                $shouldIncludeSentByEditedBy = $user && (
                    ($comment->sent_by_id !== null && in_array($comment->author_id, $accessibleTeamIds, true))
                    || ($comment->edited_by_id !== null && $canRevealMaskedPosts)
                );

                if ($shouldIncludeSentByEditedBy) {
                    $includes[] = 'sentBy';
                    $includes[] = 'editedBy';
                }

                $data = ForumTopicCommentData::from($comment);
                $data->isFromBlockedUser = in_array($comment->author_id, $maskedAuthorIds, true);

                return $data->include(...$includes);
            }
        )->all();

        $commentData = collect($forumTopicComments);
        /** @var ForumTopicCommentData|null $selectedComment */
        $selectedComment = $commentData->firstWhere('id', $selectedCommentId) ?? $commentData->first();

        if ($selectedComment->isFromBlockedUser && !$canRevealMaskedPosts) {
            $selectedComment = $commentData->last(
                fn (ForumTopicCommentData $comment): bool => !$comment->isFromBlockedUser,
            );
        }

        $metaDescription = $selectedComment
            ? Shortcode::stripAndClamp($selectedComment->body, 220)
            : $topic->title;

        $props = new ShowForumTopicPagePropsData(
            accessibleTeamAccounts: $accessibleTeamAccounts,
            replyableTeamAccounts: $replyableTeamAccounts,
            can: UserPermissionsData::fromUser($user, forumTopic: $topic)->include(
                'authorizeForumTopicComments',
                'createForumTopicComments',
                'createModerationReports',
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
            isSubscribed: $user ? (new SubscriptionService())->isSubscribed($user, SubscriptionSubjectType::ForumTopic, $topic->id) : false,
            paginatedForumTopicComments: PaginatedData::fromLengthAwarePaginator(
                $paginatedForumTopicComments,
                total: $paginatedForumTopicComments->total(),
                items: $forumTopicComments
            ),
            metaDescription: $metaDescription,
        );

        return ['props' => $props, 'redirectToPage' => null];
    }
}

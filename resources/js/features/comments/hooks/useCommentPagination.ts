import { router } from '@inertiajs/react';
import type { RouteName } from 'ziggy-js';
import { route } from 'ziggy-js';

/**
 * Maps CommentableType enum values to the route parameter names.
 */
const commentableTypeToRouteParam: Record<App.Community.Enums.CommentableType, string> = {
  'achievement.comment': 'achievement',
  'trigger.ticket.comment': 'ticket',
  'forum-topic-comment': 'forum',
  'game.comment': 'game',
  'game-hash.comment': 'game',
  'game-modification.comment': 'game',
  'leaderboard.comment': 'leaderboard',
  'achievement-set-claim.comment': 'game',
  'user.comment': 'user',
  'user-activity.comment': 'activity',
  'user-moderation.comment': 'user',
};

interface UseCommentPaginationProps {
  entityId: number;
  commentableType: App.Community.Enums.CommentableType;
  routeName: RouteName;
  paginatedComments: App.Data.PaginatedData<App.Community.Data.Comment>;

  displayName?: string;
}

export function useCommentPagination({
  entityId,
  commentableType,
  paginatedComments,
  routeName,
  displayName,
}: UseCommentPaginationProps) {
  const routeParamName = commentableTypeToRouteParam[commentableType];

  const handleCommentDeleteSuccess = () => {
    // If there are no comments left on the current page and we're not on
    // the 1st page, go back one page.
    router.visit(
      route(routeName, {
        [routeParamName]: displayName ?? entityId,
        _query: { page: getNewLastPageOnItemDelete(paginatedComments) },
      }),
      { preserveScroll: true },
    );
  };

  const handleCommentSubmitSuccess = () => {
    router.visit(
      route(routeName, {
        [routeParamName]: displayName ?? entityId,
        _query: { page: getNewLastPageOnItemAdd(paginatedComments) },
      }),
      { preserveScroll: true },
    );
  };

  const handlePageSelectValueChange = (newPageValue: number) => {
    router.visit(
      route(routeName, {
        [routeParamName]: displayName ?? entityId,
        _query: { page: newPageValue },
      }),
    );
  };

  return { handleCommentDeleteSuccess, handleCommentSubmitSuccess, handlePageSelectValueChange };
}

function getNewLastPageOnItemAdd(
  paginatedComments: App.Data.PaginatedData<App.Community.Data.Comment>,
): number {
  const { total, perPage } = paginatedComments;

  return Math.ceil((total + 1) / perPage);
}

function getNewLastPageOnItemDelete(
  paginatedComments: App.Data.PaginatedData<App.Community.Data.Comment>,
): number {
  const { total, perPage } = paginatedComments;

  return Math.ceil((total - 1) / perPage);
}

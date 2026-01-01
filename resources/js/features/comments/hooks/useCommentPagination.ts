import { router } from '@inertiajs/react';
import { useCallback } from 'react';
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

  const handleCommentDeleteSuccess = useCallback(() => {
    // If there are no comments left on the current page and we're not on
    // the 1st page, go back one page.
    router.visit(
      route(routeName, {
        [routeParamName]: displayName ?? entityId,
        _query: { page: getNewLastPageOnItemDelete(paginatedComments) },
      }),
      { preserveScroll: true },
    );
  }, [displayName, entityId, paginatedComments, routeName, routeParamName]);

  const handleCommentSubmitSuccess = useCallback(() => {
    router.visit(
      route(routeName, {
        [routeParamName]: displayName ?? entityId,
        _query: { page: getNewLastPageOnItemAdd(paginatedComments) },
      }),
      { preserveScroll: true },
    );
  }, [displayName, entityId, paginatedComments, routeName, routeParamName]);

  const handlePageSelectValueChange = useCallback(
    (newPageValue: number) => {
      router.visit(
        route(routeName, {
          [routeParamName]: displayName ?? entityId,
          _query: { page: newPageValue },
        }),
      );
    },
    [displayName, entityId, routeName, routeParamName],
  );

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

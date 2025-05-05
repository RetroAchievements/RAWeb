import { router } from '@inertiajs/react';
import { useCallback } from 'react';
import type { RouteName } from 'ziggy-js';
import { route } from 'ziggy-js';

import type { ArticleType } from '@/common/utils/generatedAppConstants';

interface UseCommentPaginationProps {
  entityId: number;
  entityType: keyof typeof ArticleType;
  routeName: RouteName;
  paginatedComments: App.Data.PaginatedData<App.Community.Data.Comment>;

  displayName?: string;
}

export function useCommentPagination({
  entityId,
  entityType,
  paginatedComments,
  routeName,
  displayName,
}: UseCommentPaginationProps) {
  const handleCommentDeleteSuccess = useCallback(() => {
    // If there are no comments left on the current page and we're not on
    // the 1st page, go back one page.
    router.visit(
      route(routeName, {
        [entityType.toLowerCase()]: displayName ?? entityId,
        _query: { page: getNewLastPageOnItemDelete(paginatedComments) },
      }),
      { preserveScroll: true },
    );
  }, [displayName, entityId, entityType, paginatedComments, routeName]);

  const handleCommentSubmitSuccess = useCallback(() => {
    router.visit(
      route(routeName, {
        [entityType.toLowerCase()]: displayName ?? entityId,
        _query: { page: getNewLastPageOnItemAdd(paginatedComments) },
      }),
      { preserveScroll: true },
    );
  }, [displayName, entityId, entityType, paginatedComments, routeName]);

  const handlePageSelectValueChange = useCallback(
    (newPageValue: number) => {
      router.visit(
        route(routeName, {
          [entityType.toLowerCase()]: displayName ?? entityId,
          _query: { page: newPageValue },
        }),
      );
    },
    [displayName, entityId, entityType, routeName],
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

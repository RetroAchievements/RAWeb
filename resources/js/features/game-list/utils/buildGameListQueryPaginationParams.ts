import type { PaginationState } from '@tanstack/react-table';

export function buildGameListQueryPaginationParams(
  pagination: PaginationState,
): Record<string, number> {
  return {
    'page[number]': pagination.pageIndex + 1,
    'page[size]': pagination.pageSize,
  };
}

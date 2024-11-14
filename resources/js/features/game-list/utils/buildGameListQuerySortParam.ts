import type { SortingState } from '@tanstack/react-table';

export function buildGameListQuerySortParam(sorting: SortingState): string | null {
  if (!sorting.length) {
    return null;
  }

  const sort = sorting[0];

  return `${sort.desc ? '-' : ''}${sort.id}`;
}

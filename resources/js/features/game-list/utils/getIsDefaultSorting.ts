import type { ColumnSort, SortingState } from '@tanstack/react-table';

export function getIsDefaultSorting(sorting: SortingState, defaultColumnSort: ColumnSort): boolean {
  return (
    sorting.length === 1 &&
    sorting[0].id === defaultColumnSort.id &&
    sorting[0].desc === defaultColumnSort.desc
  );
}

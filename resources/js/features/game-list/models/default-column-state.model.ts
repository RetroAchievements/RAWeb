import type { ColumnFiltersState, ColumnSort } from '@tanstack/react-table';

export interface DefaultColumnState {
  defaultColumnFilters: ColumnFiltersState;
  defaultColumnSort: ColumnSort;
  defaultColumnVisibility: Partial<Record<App.Platform.Enums.GameListSortField, boolean>>;
}

import type { TableState } from '@tanstack/react-table';

import type { GameListViewState } from '../models/game-list-view-state.model';

export function buildPersistedGameListViewState({
  columnFilters,
  columnVisibility,
  pagination,
  sorting,
}: GameListViewState): Partial<TableState> {
  return {
    columnFilters: columnFilters.filter((filter) => filter.id !== 'title'),
    columnVisibility,
    pagination: { ...pagination, pageIndex: 0 },
    sorting,
  };
}

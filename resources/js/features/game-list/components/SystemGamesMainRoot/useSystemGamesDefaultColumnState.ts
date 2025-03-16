import type { ColumnFiltersState, ColumnSort } from '@tanstack/react-table';
import { useMemo } from 'react';

import { usePageProps } from '@/common/hooks/usePageProps';

import type { DefaultColumnState } from '../../models';
import { buildInitialDefaultColumnVisibility } from '../../utils/buildInitialDefaultColumnVisibility';

export function useSystemGamesDefaultColumnState(): DefaultColumnState {
  const { auth, system } = usePageProps<App.Platform.Data.SystemGameListPageProps>();

  return useMemo(() => {
    const defaultColumnFilters: ColumnFiltersState = [
      { id: 'system', value: [system.id] },
      { id: 'achievementsPublished', value: ['has'] },
    ];

    const defaultColumnSort: ColumnSort = { id: 'title', desc: false };

    const defaultColumnVisibility: Partial<Record<App.Platform.Enums.GameListSortField, boolean>> =
      {
        ...buildInitialDefaultColumnVisibility(!!auth?.user),
      };

    return { defaultColumnFilters, defaultColumnSort, defaultColumnVisibility };
  }, [auth?.user, system.id]);
}

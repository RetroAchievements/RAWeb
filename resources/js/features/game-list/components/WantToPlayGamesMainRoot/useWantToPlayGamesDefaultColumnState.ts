import type { ColumnFiltersState, ColumnSort } from '@tanstack/react-table';
import { useMemo } from 'react';

import { usePageProps } from '@/common/hooks/usePageProps';

import type { DefaultColumnState } from '../../models';
import { buildInitialDefaultColumnVisibility } from '../../utils/buildInitialDefaultColumnVisibility';

export function useWantToPlayGamesDefaultColumnState(): DefaultColumnState {
  const { auth } = usePageProps();

  return useMemo(() => {
    const defaultColumnFilters: ColumnFiltersState = [
      { id: 'achievementsPublished', value: ['has'] },
    ];

    const defaultColumnSort: ColumnSort = { id: 'title', desc: false };

    const defaultColumnVisibility: Partial<Record<App.Platform.Enums.GameListSortField, boolean>> =
      {
        ...buildInitialDefaultColumnVisibility(!!auth?.user),
        progress: true,
        playersTotal: false,
      };

    return { defaultColumnFilters, defaultColumnSort, defaultColumnVisibility };
  }, [auth?.user]);
}

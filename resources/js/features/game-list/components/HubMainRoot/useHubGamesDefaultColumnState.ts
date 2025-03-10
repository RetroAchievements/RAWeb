import type { ColumnFiltersState, ColumnSort } from '@tanstack/react-table';
import { useMemo } from 'react';

import { usePageProps } from '@/common/hooks/usePageProps';

import type { DefaultColumnState } from '../../models';
import { buildInitialDefaultColumnVisibility } from '../../utils/buildInitialDefaultColumnVisibility';

export function useHubGamesDefaultColumnState(): DefaultColumnState {
  const { auth } = usePageProps();

  const defaultSubsetsValue: App.Platform.Enums.GameListSetTypeFilterValue = 'both';

  return useMemo(() => {
    const defaultColumnFilters: ColumnFiltersState = [
      { id: 'achievementsPublished', value: ['either'] },
      { id: 'subsets', value: [defaultSubsetsValue] },
    ];

    const defaultColumnSort: ColumnSort = { id: 'title', desc: false };

    const defaultColumnVisibility: Partial<Record<App.Platform.Enums.GameListSortField, boolean>> =
      {
        ...buildInitialDefaultColumnVisibility(!!auth?.user),
      };

    return { defaultColumnFilters, defaultColumnSort, defaultColumnVisibility };
  }, [auth?.user]);
}

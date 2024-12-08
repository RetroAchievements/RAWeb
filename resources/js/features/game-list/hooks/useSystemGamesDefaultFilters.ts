import type { ColumnFiltersState } from '@tanstack/react-table';

import { usePageProps } from '@/common/hooks/usePageProps';

export function useSystemGamesDefaultFilters() {
  const { system } = usePageProps<App.Platform.Data.SystemGameListPageProps>();

  const systemGamesDefaultFilters: ColumnFiltersState = [
    { id: 'system', value: [system.id] },
    { id: 'achievementsPublished', value: ['has'] },
  ];

  return { systemGamesDefaultFilters };
}

import { useQuery } from '@tanstack/react-query';
import type { ColumnFiltersState } from '@tanstack/react-table';
import axios from 'axios';
import type { RouteName } from 'ziggy-js';

import { buildGameListQueryFilterParams } from '@/features/game-list/utils/buildGameListQueryFilterParams';

interface UseRandomGameQueryProps {
  columnFilters: ColumnFiltersState;

  apiRouteName?: RouteName;
}

export function useRandomGameQuery({
  columnFilters,
  apiRouteName = 'api.game.random',
}: UseRandomGameQueryProps) {
  return useQuery({
    queryKey: ['random-game', columnFilters, apiRouteName],

    queryFn: async () => {
      const response = await axios.get<{ gameId: number }>(
        route(apiRouteName, buildGameListQueryFilterParams(columnFilters)),
      );

      return response.data;
    },

    refetchInterval: Infinity,
    refetchOnWindowFocus: false,
  });
}

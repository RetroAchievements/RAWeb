import { keepPreviousData, useQuery } from '@tanstack/react-query';
import type { ColumnFiltersState, PaginationState, SortingState } from '@tanstack/react-table';
import axios from 'axios';

import { buildGameListQueryFilterParams } from '../../utils/buildGameListQueryFilterParams';
import { buildGameListQuerySortParam } from '../../utils/buildGameListQuerySortParam';

const ONE_MINUTE = 1 * 60 * 1000;

interface UseGameListQueryProps {
  pagination: PaginationState;
  sorting: SortingState;
  columnFilters: ColumnFiltersState;
}

export function useGameListQuery({ columnFilters, pagination, sorting }: UseGameListQueryProps) {
  const dataQuery = useQuery<App.Data.PaginatedData<App.Platform.Data.GameListEntry>>({
    queryKey: ['data', pagination, sorting, columnFilters],

    queryFn: async () => {
      const response = await axios.get<App.Data.PaginatedData<App.Platform.Data.GameListEntry>>(
        route('api.user-game-list.index', {
          'page[number]': pagination.pageIndex + 1,
          sort: buildGameListQuerySortParam(sorting),
          ...buildGameListQueryFilterParams(columnFilters),
        }),
      );

      return response.data;
    },

    staleTime: ONE_MINUTE,
    placeholderData: keepPreviousData,
  });

  return dataQuery;
}

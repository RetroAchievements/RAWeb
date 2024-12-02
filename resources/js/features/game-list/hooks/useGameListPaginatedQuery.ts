import { keepPreviousData, useQuery } from '@tanstack/react-query';
import type { ColumnFiltersState, PaginationState, SortingState } from '@tanstack/react-table';
import axios from 'axios';
import type { RouteName } from 'ziggy-js';

import { buildGameListQueryFilterParams } from '../utils/buildGameListQueryFilterParams';
import { buildGameListQueryPaginationParams } from '../utils/buildGameListQueryPaginationParams';
import { buildGameListQuerySortParam } from '../utils/buildGameListQuerySortParam';

const ONE_MINUTE = 1 * 60 * 1000;

interface UseGameListPaginatedQueryProps {
  pagination: PaginationState;
  sorting: SortingState;
  columnFilters: ColumnFiltersState;

  /**
   * Defaults to true. If false, the query will never fire.
   * Useful when a different query is being used instead, ie: mobile environments
   * use the useGameListInfiniteQuery hook, but both hooks are present on the page.
   */
  isEnabled?: boolean;

  apiRouteName?: RouteName;
}

export function useGameListPaginatedQuery({
  columnFilters,
  pagination,
  sorting,
  isEnabled = true,
  apiRouteName = 'api.game.index',
}: UseGameListPaginatedQueryProps) {
  return useQuery<App.Data.PaginatedData<App.Platform.Data.GameListEntry>>({
    // eslint-disable-next-line @tanstack/query/exhaustive-deps -- tableApiRouteName is not part of the key
    queryKey: ['data', pagination, sorting, columnFilters],

    queryFn: async () => {
      const response = await axios.get<App.Data.PaginatedData<App.Platform.Data.GameListEntry>>(
        route(apiRouteName, {
          sort: buildGameListQuerySortParam(sorting),
          ...buildGameListQueryPaginationParams(pagination),
          ...buildGameListQueryFilterParams(columnFilters),
        }),
      );

      return response.data;
    },

    staleTime: ONE_MINUTE,
    placeholderData: keepPreviousData,

    enabled: isEnabled,
  });
}

import { useQueryClient } from '@tanstack/react-query';
import type { ColumnFiltersState, Table } from '@tanstack/react-table';
import axios from 'axios';
import type { RouteName } from 'ziggy-js';
import { route } from 'ziggy-js';

import { buildGameListQueryFilterParams } from '../utils/buildGameListQueryFilterParams';
import { buildGameListQueryPaginationParams } from '../utils/buildGameListQueryPaginationParams';
import { buildGameListQuerySortParam } from '../utils/buildGameListQuerySortParam';

/**
 * Given the user hovers over the Reset button, it is very likely they will
 * wind up clicking the button. Queries are cheap, so prefetch the destination.
 */

export function useDataTablePrefetchResetFilters<TData>(
  table: Table<TData>,
  defaultColumnFilters: ColumnFiltersState,
  tableApiRouteName: RouteName,
  tableApiRouteParams?: Record<string, unknown>,
) {
  const queryClient = useQueryClient();

  const { pagination, sorting } = table.getState();

  const prefetchResetFilters = () => {
    queryClient.prefetchQuery({
      queryKey: [
        'data',
        tableApiRouteName,
        pagination,
        sorting,
        defaultColumnFilters,
        tableApiRouteParams,
      ],

      queryFn: async () => {
        const response = await axios.get<App.Data.PaginatedData<App.Platform.Data.GameListEntry>>(
          route(tableApiRouteName, {
            ...tableApiRouteParams,
            sort: buildGameListQuerySortParam(sorting),
            ...buildGameListQueryPaginationParams(pagination),
            ...buildGameListQueryFilterParams(defaultColumnFilters),
          }),
        );

        return response.data;
      },

      staleTime: 1 * 60 * 1000, // 1 minute
    });
  };

  return { prefetchResetFilters };
}

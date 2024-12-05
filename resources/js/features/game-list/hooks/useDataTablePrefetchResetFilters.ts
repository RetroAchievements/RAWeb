import { useQueryClient } from '@tanstack/react-query';
import type { ColumnFiltersState, Table } from '@tanstack/react-table';
import axios from 'axios';
import type { RouteName } from 'ziggy-js';

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
  const { pagination, sorting } = table.getState();

  const queryClient = useQueryClient();

  const prefetchResetFilters = () => {
    queryClient.prefetchQuery({
      // eslint-disable-next-line @tanstack/query/exhaustive-deps -- tableApiRouteName is not part of the key
      queryKey: ['data', pagination, sorting, defaultColumnFilters],
      staleTime: 1 * 60 * 1000, // 1 minute
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
    });
  };

  return { prefetchResetFilters };
}

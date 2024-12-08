import { useQueryClient } from '@tanstack/react-query';
import type { Table } from '@tanstack/react-table';
import axios from 'axios';
import type { RouteName } from 'ziggy-js';

import { buildGameListQueryFilterParams } from '../utils/buildGameListQueryFilterParams';
import { buildGameListQueryPaginationParams } from '../utils/buildGameListQueryPaginationParams';
import { buildGameListQuerySortParam } from '../utils/buildGameListQuerySortParam';

/**
 * Given the user hovers over a pagination button, it is very likely they will
 * wind up clicking the button. Queries are cheap, so prefetch the destination page.
 */

export function useDataTablePrefetchPagination<TData>(
  table: Table<TData>,
  tableApiRouteName: RouteName,
  tableApiRouteParams?: Record<string, unknown>,
) {
  const { columnFilters, sorting } = table.getState();

  const queryClient = useQueryClient();

  const prefetchPagination = (params: { newPageIndex: number; newPageSize: number }) => {
    queryClient.prefetchQuery({
      // eslint-disable-next-line @tanstack/query/exhaustive-deps -- tableApiRouteName is not part of the key
      queryKey: [
        'data',
        { pageIndex: params.newPageIndex, pageSize: params.newPageSize },
        sorting,
        columnFilters,
      ],

      queryFn: async () => {
        const response = await axios.get<App.Data.PaginatedData<App.Platform.Data.GameListEntry>>(
          route(tableApiRouteName, {
            ...tableApiRouteParams,

            sort: buildGameListQuerySortParam(sorting),

            ...buildGameListQueryPaginationParams({
              pageSize: params.newPageSize,
              pageIndex: params.newPageIndex,
            }),

            ...buildGameListQueryFilterParams(columnFilters),
          }),
        );

        return response.data;
      },

      staleTime: 1 * 60 * 1000, // 1 minute
    });
  };

  return { prefetchPagination };
}

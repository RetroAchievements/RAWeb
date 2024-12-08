import { useQueryClient } from '@tanstack/react-query';
import type { Table } from '@tanstack/react-table';
import axios from 'axios';
import type { RouteName } from 'ziggy-js';

import { buildGameListQueryFilterParams } from '../utils/buildGameListQueryFilterParams';
import { buildGameListQueryPaginationParams } from '../utils/buildGameListQueryPaginationParams';
import { buildGameListQuerySortParam } from '../utils/buildGameListQuerySortParam';

/**
 * Given the user hovers over a sort option, it is very likely they will
 * wind up clicking the option. Queries are cheap, so prefetch the destination.
 */

export function useDataTablePrefetchSort<TData>(
  table: Table<TData>,
  tableApiRouteName: RouteName,
  tableApiRouteParams?: Record<string, unknown>,
) {
  const { columnFilters, pagination } = table.getState();

  const queryClient = useQueryClient();

  /**
   * Note that we go out of our way to always set pageIndex to 0.
   * This is because sorting will always pop the user back to the first page.
   */

  const prefetchSort = (columnId = '', direction: 'asc' | 'desc') => {
    queryClient.prefetchQuery({
      // eslint-disable-next-line @tanstack/query/exhaustive-deps -- tableApiRouteName is not part of the key
      queryKey: [
        'data',
        { ...pagination, pageIndex: 0 },
        [{ id: columnId, desc: direction === 'desc' }],
        columnFilters,
      ],
      staleTime: 1 * 60 * 1000, // 1 minute
      queryFn: async () => {
        const response = await axios.get<App.Data.PaginatedData<App.Platform.Data.GameListEntry>>(
          route(tableApiRouteName, {
            ...tableApiRouteParams,
            sort: buildGameListQuerySortParam([{ id: columnId, desc: direction === 'desc' }]),
            ...buildGameListQueryPaginationParams({ ...pagination, pageIndex: 0 }),
            ...buildGameListQueryFilterParams(columnFilters),
          }),
        );

        return response.data;
      },
    });
  };

  return { prefetchSort };
}

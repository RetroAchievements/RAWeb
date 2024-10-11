import { useQueryClient } from '@tanstack/react-query';
import type { Table } from '@tanstack/react-table';
import axios from 'axios';
import type { RouteName } from 'ziggy-js';

import { buildGameListQueryFilterParams } from '@/common/utils/buildGameListQueryFilterParams';
import { buildGameListQuerySortParam } from '@/common/utils/buildGameListQuerySortParam';

/**
 * Given the user hovers over a pagination button, it is very likely they will
 * wind up clicking the button. Queries are cheap, so prefetch the destination page.
 */

export function useDataTablePrefetchPagination<TData>(
  table: Table<TData>,
  tableApiRouteName: RouteName,
) {
  const { columnFilters, pagination, sorting } = table.getState();

  const queryClient = useQueryClient();

  const prefetchPagination = (newPageIndex: number) => {
    queryClient.prefetchQuery({
      // eslint-disable-next-line @tanstack/query/exhaustive-deps -- tableApiRouteName is not part of the key
      queryKey: [
        'data',
        { pageIndex: newPageIndex, pageSize: pagination.pageSize },
        sorting,
        columnFilters,
      ],
      staleTime: 1 * 60 * 1000, // 1 minute
      queryFn: async () => {
        const response = await axios.get<App.Data.PaginatedData<App.Platform.Data.GameListEntry>>(
          route(tableApiRouteName, {
            'page[number]': newPageIndex + 1,
            sort: buildGameListQuerySortParam(sorting),
            ...buildGameListQueryFilterParams(columnFilters),
          }),
        );

        return response.data;
      },
    });
  };

  return { prefetchPagination };
}

import { useQueryClient } from '@tanstack/react-query';
import type { Table } from '@tanstack/react-table';
import axios from 'axios';

import { buildGameListQueryFilterParams } from '@/common/utils/buildGameListQueryFilterParams';
import { buildGameListQuerySortParam } from '@/common/utils/buildGameListQuerySortParam';

/**
 * Given the user hovers over a pagination button, it is very likely they will
 * wind up clicking the button. Queries are cheap, so prefetch the destination page.
 */

export function usePrefetchPagination<TData>(table: Table<TData>) {
  const { columnFilters, pagination, sorting } = table.getState();

  const queryClient = useQueryClient();

  const prefetchPagination = (newPageIndex: number) => {
    queryClient.prefetchQuery({
      queryKey: [
        'data',
        { pageIndex: newPageIndex, pageSize: pagination.pageSize },
        sorting,
        columnFilters,
      ],
      staleTime: 1 * 60 * 1000, // 1 minute
      queryFn: async () => {
        const response = await axios.get<App.Data.PaginatedData<App.Platform.Data.GameListEntry>>(
          route('api.user-game-list.index', {
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

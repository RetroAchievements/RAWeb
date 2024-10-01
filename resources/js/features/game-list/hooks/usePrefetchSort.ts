import { useQueryClient } from '@tanstack/react-query';
import type { Table } from '@tanstack/react-table';
import axios from 'axios';

import { buildGameListQueryFilterParams } from '@/common/utils/buildGameListQueryFilterParams';
import { buildGameListQuerySortParam } from '@/common/utils/buildGameListQuerySortParam';

/**
 * Given the user hovers over a sort option, it is very likely they will
 * wind up clicking the option. Queries are cheap, so prefetch the destination.
 */

export function usePrefetchSort<TData>(table: Table<TData>) {
  const { columnFilters, pagination } = table.getState();

  const queryClient = useQueryClient();

  const prefetchSort = (columnId = '', direction: 'asc' | 'desc') => {
    queryClient.prefetchQuery({
      queryKey: ['data', pagination, [{ id: columnId, desc: direction === 'desc' }], columnFilters],
      staleTime: 1 * 60 * 1000, // 1 minute
      queryFn: async () => {
        const response = await axios.get<App.Data.PaginatedData<App.Platform.Data.GameListEntry>>(
          route('api.user-game-list.index', {
            'page[number]': pagination.pageIndex + 1,
            sort: buildGameListQuerySortParam([{ id: columnId, desc: direction === 'desc' }]),
            ...buildGameListQueryFilterParams(columnFilters),
          }),
        );

        return response.data;
      },
    });
  };

  return { prefetchSort };
}

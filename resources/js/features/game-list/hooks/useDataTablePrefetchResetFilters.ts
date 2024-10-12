import { useQueryClient } from '@tanstack/react-query';
import type { ColumnFiltersState, Table } from '@tanstack/react-table';
import axios from 'axios';
import type { RouteName } from 'ziggy-js';

import { buildGameListQueryFilterParams } from '@/common/utils/buildGameListQueryFilterParams';
import { buildGameListQuerySortParam } from '@/common/utils/buildGameListQuerySortParam';

/**
 * Given the user hovers over the Reset button, it is very likely they will
 * wind up clicking the button. Queries are cheap, so prefetch the destination.
 */

export function useDataTablePrefetchResetFilters<TData>(
  table: Table<TData>,
  defaultColumnFilters: ColumnFiltersState,
  tableApiRouteName: RouteName,
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
            'page[number]': pagination.pageIndex + 1,
            sort: buildGameListQuerySortParam(sorting),
            ...buildGameListQueryFilterParams(defaultColumnFilters),
          }),
        );

        return response.data;
      },
    });
  };

  return { prefetchResetFilters };
}

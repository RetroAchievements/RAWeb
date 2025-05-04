import { useInfiniteQuery } from '@tanstack/react-query';
import type { ColumnFiltersState, PaginationState, SortingState } from '@tanstack/react-table';
import axios from 'axios';
import type { RouteName } from 'ziggy-js';
import { route } from 'ziggy-js';

import { buildGameListQueryFilterParams } from '../utils/buildGameListQueryFilterParams';
import { buildGameListQuerySortParam } from '../utils/buildGameListQuerySortParam';

const ONE_MINUTE = 1 * 60 * 1000;

interface UseGameListInfiniteQueryProps {
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
  apiRouteParams?: Record<string, unknown>;
}

export function useGameListInfiniteQuery({
  columnFilters,
  pagination,
  sorting,
  apiRouteParams,
  isEnabled = true,
  apiRouteName = 'api.game.index',
}: UseGameListInfiniteQueryProps) {
  return useInfiniteQuery<App.Data.PaginatedData<App.Platform.Data.GameListEntry>>({
    // eslint-disable-next-line @tanstack/query/exhaustive-deps -- tableApiRouteName is not part of the key
    queryKey: ['infinite-data', pagination, sorting, columnFilters],

    queryFn: async ({ pageParam }) => {
      const response = await axios.get<App.Data.PaginatedData<App.Platform.Data.GameListEntry>>(
        route(apiRouteName, {
          ...apiRouteParams,
          'page[number]': pageParam,
          sort: buildGameListQuerySortParam(sorting),
          ...buildGameListQueryFilterParams(columnFilters),
        }),
      );

      return response.data;
    },

    initialPageParam: 1,

    getNextPageParam: (previouslyFetchedPage) => {
      // If we're on the last page, return null so the UI doesn't try to fetch another page.
      if (previouslyFetchedPage.currentPage === previouslyFetchedPage.lastPage) {
        return null;
      }

      return previouslyFetchedPage.currentPage + 1;
    },

    staleTime: ONE_MINUTE,
    refetchOnWindowFocus: false,
    refetchOnReconnect: false,

    enabled: isEnabled,
  });
}

import { keepPreviousData, useInfiniteQuery } from '@tanstack/react-query';
import axios from 'axios';

interface UseActivePlayersInfiniteQueryProps {
  isEnabled: boolean;

  initialData?: App.Data.PaginatedData<App.Community.Data.ActivePlayer>;
  perPage?: number;
  search?: string;
}

export function useActivePlayersInfiniteQuery({
  initialData,
  isEnabled,
  search,
  perPage = 100,
}: UseActivePlayersInfiniteQueryProps) {
  return useInfiniteQuery({
    initialData: initialData ? { pageParams: [1], pages: [initialData] } : undefined,

    queryKey: ['active-players', { search, perPage }],

    queryFn: async ({ pageParam }) => {
      const response = await axios.get<App.Data.PaginatedData<App.Community.Data.ActivePlayer>>(
        route('api.active-player.index', { search, perPage, page: pageParam }),
      );

      return response.data;
    },

    initialPageParam: 1,

    getNextPageParam: (previouslyFetchedPage) => {
      // If we're on the last page, return null so the UI doesn't try to fetch another page.
      if (
        !previouslyFetchedPage ||
        previouslyFetchedPage.currentPage === previouslyFetchedPage.lastPage
      ) {
        return null;
      }

      return previouslyFetchedPage.currentPage + 1;
    },

    staleTime: (queryInfo) => {
      const FIVE_MINUTES = 5 * 60 * 1000;

      // If there are many pages open, don't invalidate them.
      // This is a safeguard to prevent the browser from initiating
      // potentially dozens of API requests to refetch all the pages.
      const loadedPageCount = queryInfo.state.data?.pages.length ?? 0;

      return loadedPageCount >= 5 ? Infinity : FIVE_MINUTES;
    },

    enabled: isEnabled,
    placeholderData: keepPreviousData,
  });
}

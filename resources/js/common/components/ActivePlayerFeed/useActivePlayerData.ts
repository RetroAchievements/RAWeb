import { useMemo } from 'react';

import { useActivePlayersInfiniteQuery } from './useActivePlayersInfiniteQuery';

interface UseActivePlayerDataProps {
  initialActivePlayers: App.Data.PaginatedData<App.Community.Data.ActivePlayer>;
  isInfiniteQueryEnabled: boolean;
  searchValue: string;
}

export function useActivePlayerData({
  initialActivePlayers,
  isInfiniteQueryEnabled,
  searchValue,
}: UseActivePlayerDataProps) {
  const infiniteQuery = useActivePlayersInfiniteQuery({
    initialData: !isInfiniteQueryEnabled ? initialActivePlayers : undefined,
    isEnabled: isInfiniteQueryEnabled,
    search: searchValue,
    perPage: isInfiniteQueryEnabled ? 100 : 20,
  });

  const players = useMemo(() => {
    if (!isInfiniteQueryEnabled) {
      return initialActivePlayers.items;
    }

    if (infiniteQuery.isLoading && !infiniteQuery.data) {
      return initialActivePlayers.items;
    }

    return infiniteQuery.data?.pages.flatMap((page) => page.items) ?? initialActivePlayers.items;
  }, [
    initialActivePlayers.items,
    infiniteQuery.data,
    infiniteQuery.isLoading,
    isInfiniteQueryEnabled,
  ]);

  return { players, loadMore: () => infiniteQuery.fetchNextPage() };
}

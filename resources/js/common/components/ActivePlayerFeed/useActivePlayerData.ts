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

  const canUseInfiniteData = isInfiniteQueryEnabled && !infiniteQuery.isLoading;
  const players = canUseInfiniteData
    ? (infiniteQuery.data?.pages.flatMap((page) => page.items) ?? initialActivePlayers.items)
    : initialActivePlayers.items;

  return { players, loadMore: () => infiniteQuery.fetchNextPage() };
}

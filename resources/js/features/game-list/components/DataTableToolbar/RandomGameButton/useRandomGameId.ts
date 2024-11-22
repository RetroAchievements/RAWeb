import { useMutation } from '@tanstack/react-query';
import type { ColumnFiltersState } from '@tanstack/react-table';
import axios from 'axios';
import { useRef, useState } from 'react';
import type { RouteName } from 'ziggy-js';

import { usePageProps } from '@/common/hooks/usePageProps';
import { buildGameListQueryFilterParams } from '@/features/game-list/utils/buildGameListQueryFilterParams';

interface UseRandomGameIdProps {
  apiRouteName: RouteName;
  columnFilters: ColumnFiltersState;
}

export function useRandomGameId({ apiRouteName, columnFilters }: UseRandomGameIdProps) {
  const {
    ziggy: { device },
  } = usePageProps();

  const [prefetchedGameId, setPrefetchedGameId] = useState<number | null>(null);

  const currentPromise = useRef<Promise<{ gameId: number }> | null>(null);

  const mutation = useMutation({
    mutationFn: async () => {
      const response = await axios.get<{ gameId: number }>(
        route(apiRouteName, buildGameListQueryFilterParams(columnFilters)),
      );

      return response.data;
    },

    onSuccess: (data) => setPrefetchedGameId(data.gameId),
  });

  const prefetchRandomGameId = (options?: { shouldForce: boolean }) => {
    if (!options?.shouldForce && (device === 'mobile' || prefetchedGameId || mutation.isPending)) {
      return;
    }

    const promise = mutation.mutateAsync();
    currentPromise.current = promise;

    promise.finally(() => {
      currentPromise.current = null;
    });
  };

  const getRandomGameId = async () => {
    if (prefetchedGameId) {
      const gameId = prefetchedGameId;
      setPrefetchedGameId(null);

      return gameId;
    }

    if (mutation.isPending && currentPromise.current) {
      const result = await currentPromise.current;
      setPrefetchedGameId(null);

      return result.gameId;
    }

    const result = await mutation.mutateAsync();

    return result.gameId;
  };

  return { getRandomGameId, prefetchRandomGameId };
}

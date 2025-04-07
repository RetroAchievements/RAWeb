import { useMutation } from '@tanstack/react-query';
import type { ColumnFiltersState } from '@tanstack/react-table';
import axios from 'axios';
import { useRef } from 'react';
import type { RouteName } from 'ziggy-js';

import { usePageProps } from '@/common/hooks/usePageProps';
import { buildGameListQueryFilterParams } from '@/features/game-list/utils/buildGameListQueryFilterParams';

interface UseRandomGameIdProps {
  apiRouteName: RouteName;
  columnFilters: ColumnFiltersState;

  apiRouteParams?: Record<string, unknown>;
}

export function useRandomGameId({
  apiRouteName,
  apiRouteParams,
  columnFilters,
}: UseRandomGameIdProps) {
  const {
    ziggy: { device },
  } = usePageProps();

  const prefetchedResult = useRef<{ gameId: number; filters: ColumnFiltersState } | null>(null);

  const currentPromise = useRef<Promise<{ gameId: number }> | null>(null);

  const mutation = useMutation({
    mutationFn: async () => {
      const response = await axios.get<{ gameId: number }>(
        route(apiRouteName, {
          ...buildGameListQueryFilterParams(columnFilters),
          ...apiRouteParams,
        }),
      );

      return response.data;
    },

    onSuccess: (data) => {
      prefetchedResult.current = {
        gameId: data.gameId,
        filters: [...columnFilters],
      };
    },
  });

  const prefetchRandomGameId = (options?: { shouldForce: boolean }) => {
    const areFiltersEqual =
      prefetchedResult.current &&
      getAreFiltersEqual(prefetchedResult.current.filters, columnFilters);

    if (!options?.shouldForce && (device === 'mobile' || areFiltersEqual || mutation.isPending)) {
      return;
    }

    const promise = mutation.mutateAsync();
    currentPromise.current = promise;

    promise.finally(() => {
      currentPromise.current = null;
    });
  };

  const getRandomGameId = async () => {
    const areFiltersEqual =
      prefetchedResult.current &&
      getAreFiltersEqual(prefetchedResult.current.filters, columnFilters);

    if (prefetchedResult.current && areFiltersEqual) {
      const { gameId } = prefetchedResult.current;
      prefetchedResult.current = null;

      return gameId;
    }

    if (mutation.isPending && currentPromise.current) {
      const result = await currentPromise.current;
      prefetchedResult.current = null;

      return result.gameId;
    }

    const result = await mutation.mutateAsync();

    return result.gameId;
  };

  return { getRandomGameId, prefetchRandomGameId };
}

const getAreFiltersEqual = (filters1: ColumnFiltersState, filters2: ColumnFiltersState): boolean =>
  JSON.stringify(filters1) === JSON.stringify(filters2);

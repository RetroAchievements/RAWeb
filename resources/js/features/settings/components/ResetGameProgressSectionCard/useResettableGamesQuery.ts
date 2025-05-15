import { useQuery } from '@tanstack/react-query';
import axios from 'axios';
import { route } from 'ziggy-js';

export function useResettableGamesQuery(isEnabled: boolean) {
  return useQuery({
    queryKey: ['resettable-games'],
    queryFn: async () => {
      const response = await axios.get<{ results: App.Platform.Data.PlayerResettableGame[] }>(
        route('player.games.resettable'),
      );

      return response.data.results;
    },
    enabled: isEnabled,
    refetchInterval: false,
    staleTime: 60 * 1000, // one minute
  });
}

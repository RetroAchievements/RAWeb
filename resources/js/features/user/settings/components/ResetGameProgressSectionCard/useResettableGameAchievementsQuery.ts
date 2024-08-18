import { useQuery } from '@tanstack/react-query';
import axios from 'axios';
import { route } from 'ziggy-js';

export function useResettableGameAchievementsQuery(gameId: string | null) {
  return useQuery({
    queryKey: ['resettable-game-achievements', gameId],
    queryFn: async () => {
      const response = await axios.get<{
        results: App.Platform.Data.PlayerResettableGameAchievement[];
      }>(route('player.game.achievements.resettable', gameId ?? 0));

      return response.data.results;
    },
    enabled: !!gameId,
    refetchInterval: false,
    staleTime: 60 * 1000, // one minute
  });
}

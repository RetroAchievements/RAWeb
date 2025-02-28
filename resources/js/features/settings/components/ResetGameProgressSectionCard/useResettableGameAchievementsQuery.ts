import { useQuery } from '@tanstack/react-query';
import axios from 'axios';

export function useResettableGameAchievementsQuery(gameId: string) {
  return useQuery({
    queryKey: ['resettable-game-achievements', gameId],

    queryFn: async () => {
      const response = await axios.get<{
        results: App.Platform.Data.PlayerResettableGameAchievement[];
      }>(route('player.game.achievements.resettable', gameId));

      return response.data.results;
    },

    enabled: !!gameId,
    refetchInterval: false,
    staleTime: 60 * 1000, // one minute
  });
}

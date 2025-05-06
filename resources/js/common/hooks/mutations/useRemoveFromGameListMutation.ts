import { useMutation } from '@tanstack/react-query';
import axios from 'axios';
import { route } from 'ziggy-js';

export function useRemoveFromGameListMutation() {
  return useMutation({
    mutationFn: (gameId: number, userGameListType: App.Community.Enums.UserGameListType = 'play') =>
      axios.delete(route('api.user-game-list.destroy', gameId), { data: { userGameListType } }),
  });
}

import { useMutation } from '@tanstack/react-query';
import axios from 'axios';

export function useAddToGameListMutation() {
  return useMutation({
    mutationFn: (gameId: number, userGameListType: App.Community.Enums.UserGameListType = 'play') =>
      axios.post(route('api.user-game-list.store', gameId), { userGameListType }),
  });
}

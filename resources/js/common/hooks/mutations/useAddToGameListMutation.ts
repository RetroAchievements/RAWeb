import { useMutation } from '@tanstack/react-query';
import axios from 'axios';
import { route } from 'ziggy-js';

interface Variables {
  gameId: number;
  userGameListType: App.Community.Enums.UserGameListType;
}

export function useAddToGameListMutation() {
  return useMutation({
    mutationFn: ({ gameId, userGameListType }: Variables) =>
      axios.post(route('api.user-game-list.store', gameId), { userGameListType }),
  });
}

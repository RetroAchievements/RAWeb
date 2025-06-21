import { useMutation } from '@tanstack/react-query';
import axios from 'axios';
import { route } from 'ziggy-js';

interface Variables {
  gameId: number;
  userGameListType: App.Community.Enums.UserGameListType;
}

export function useRemoveFromGameListMutation() {
  return useMutation({
    mutationFn: ({ gameId, userGameListType }: Variables) =>
      axios.delete(route('api.user-game-list.destroy', gameId), { data: { userGameListType } }),
  });
}

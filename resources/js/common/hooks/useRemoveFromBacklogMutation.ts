import { useMutation } from '@tanstack/react-query';
import axios from 'axios';
import type { ValueOf } from 'type-fest';

import { UserGameListType } from '../utils/generatedAppConstants';

export function useRemoveFromBacklogMutation() {
  return useMutation({
    mutationFn: (
      gameId: number,
      userGameListType: ValueOf<typeof UserGameListType> = UserGameListType.Play,
    ) => axios.delete(route('api.user-game-list.destroy', gameId), { data: { userGameListType } }),
  });
}

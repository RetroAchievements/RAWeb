import { useMutation } from '@tanstack/react-query';
import axios from 'axios';
import type { ValueOf } from 'type-fest';

import { UserGameListType } from '../utils/generatedAppConstants';

export function useAddToBacklogMutation() {
  return useMutation({
    mutationFn: (
      gameId: number,
      userGameListType: ValueOf<typeof UserGameListType> = UserGameListType.Play,
    ) => axios.post(route('api.user-game-list.store', gameId), { userGameListType }),
  });
}

import { useMutation } from '@tanstack/react-query';
import axios from 'axios';
import { route } from 'ziggy-js';

interface Variables {
  payload: { game: number };
}

export function useResetGameProgressMutation() {
  return useMutation({
    mutationFn: ({ payload }: Variables) => axios.delete(route('api.user.game.destroy', payload)),
  });
}

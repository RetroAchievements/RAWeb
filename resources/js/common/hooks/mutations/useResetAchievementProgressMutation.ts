import { useMutation } from '@tanstack/react-query';
import axios from 'axios';
import { route } from 'ziggy-js';

interface Variables {
  payload: { achievement: number };
}

export function useResetAchievementProgressMutation() {
  return useMutation({
    mutationFn: ({ payload }: Variables) =>
      axios.delete(route('api.user.achievement.destroy', payload)),
  });
}

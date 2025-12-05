import { useMutation } from '@tanstack/react-query';
import axios from 'axios';
import { route } from 'ziggy-js';

interface Variables {
  payload: {
    preferences: Array<{ gameAchievementSetId: number; optedIn: boolean }>;
  };
}

export function useUpdateGameAchievementSetPreferencesMutation() {
  return useMutation({
    mutationFn: ({ payload }: Variables) =>
      axios.put(route('api.user.game-achievement-set.preferences.update'), payload),
  });
}

import { useMutation } from '@tanstack/react-query';
import axios from 'axios';
import { route } from 'ziggy-js';

interface Variables {
  achievementId: number;

  payload: Partial<{
    description: string;
    isPromoted: boolean;
    points: number;
    title: string;
    type: string | null;
  }>;
}

export function useUpdateAchievementMutation() {
  return useMutation({
    mutationFn: ({ achievementId, payload }: Variables) =>
      axios.patch(route('api.achievement.update', { achievement: achievementId }), payload),
  });
}

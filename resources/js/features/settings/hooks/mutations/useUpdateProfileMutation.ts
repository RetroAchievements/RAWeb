import { useMutation } from '@tanstack/react-query';
import axios from 'axios';
import { route } from 'ziggy-js';

interface Variables {
  payload: Partial<{
    motto: string | null;
    userWallActive: boolean | null;
    visibleRoleId: number | null;
  }>;
}

export function useUpdateProfileMutation() {
  return useMutation({
    mutationFn: ({ payload }: Variables) => {
      return axios.put(route('api.settings.profile.update'), payload);
    },
  });
}

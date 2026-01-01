import { useMutation } from '@tanstack/react-query';
import axios from 'axios';
import { route } from 'ziggy-js';

interface Variables {
  payload: Partial<{
    preferencesBitfield: number;
    suppressMatureContentWarning: boolean;
  }>;
}

export function useUpdatePreferencesMutation() {
  return useMutation({
    mutationFn: ({ payload }: Variables) => {
      return axios.put(route('api.settings.preferences.update'), payload);
    },
  });
}

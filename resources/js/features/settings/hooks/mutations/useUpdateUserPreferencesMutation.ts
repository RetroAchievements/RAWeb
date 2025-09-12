import { useMutation } from '@tanstack/react-query';
import axios from 'axios';
import { route } from 'ziggy-js';

export function useUpdateUserPreferencesMutation() {
  return useMutation({
    mutationFn: (websitePrefs: number) => {
      return axios.put(route('api.settings.preferences.update'), { websitePrefs });
    },
  });
}

import { useMutation } from '@tanstack/react-query';
import axios from 'axios';
import { route } from 'ziggy-js';

interface Variables {
  payload: {
    locale: string;
  };
}

export function useUpdateLocaleMutation() {
  return useMutation({
    mutationFn: ({ payload }: Variables) => {
      return axios.put(route('api.settings.locale.update'), payload);
    },
  });
}

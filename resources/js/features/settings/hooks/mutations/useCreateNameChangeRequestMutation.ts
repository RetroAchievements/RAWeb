import { useMutation } from '@tanstack/react-query';
import axios from 'axios';
import { route } from 'ziggy-js';

interface Variables {
  payload: {
    newDisplayName: string;
  };
}

export function useCreateNameChangeRequestMutation() {
  return useMutation({
    mutationFn: ({ payload }: Variables) => {
      return axios.post(route('api.settings.name-change-request.store'), payload);
    },
  });
}

import { useMutation } from '@tanstack/react-query';
import axios from 'axios';
import { route } from 'ziggy-js';

interface Variables {
  payload: {
    newEmail: string;
    confirmEmail: string;
  };
}

export function useChangeEmailAddressMutation() {
  return useMutation({
    mutationFn: ({ payload }: Variables) => {
      return axios.put(route('api.settings.email.update'), payload);
    },
  });
}

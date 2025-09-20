import { useMutation } from '@tanstack/react-query';
import axios from 'axios';
import { route } from 'ziggy-js';

interface Variables {
  payload: {
    confirmPassword: string;
    currentPassword: string;
    newPassword: string;
  };
}

export function useChangePasswordMutation() {
  return useMutation({
    mutationFn: ({ payload }: Variables) => {
      return axios.put(route('api.settings.password.update'), payload);
    },
  });
}

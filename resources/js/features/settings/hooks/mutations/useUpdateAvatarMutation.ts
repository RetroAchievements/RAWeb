import { useMutation } from '@tanstack/react-query';
import axios from 'axios';
import { route } from 'ziggy-js';

interface Variables {
  formData: FormData;
}

export function useUpdateAvatarMutation() {
  return useMutation({
    mutationFn: ({ formData }: Variables) => {
      return axios.post(route('api.user.avatar.store'), formData, {
        headers: {
          'Content-Type': 'multipart/form-data',
        },
      });
    },
  });
}

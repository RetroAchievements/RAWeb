import { useMutation } from '@tanstack/react-query';
import axios from 'axios';
import { route } from 'ziggy-js';

import { UpdateAvatarResponse } from '../../models';

interface Variables {
  formData: FormData;
}

export function useUpdateAvatarMutation() {
  return useMutation({
    mutationFn: ({ formData }: Variables) => {
      return axios.post<UpdateAvatarResponse>(route('api.user.avatar.store'), formData, {
        headers: {
          'Content-Type': 'multipart/form-data',
        },
      });
    },
  });
}

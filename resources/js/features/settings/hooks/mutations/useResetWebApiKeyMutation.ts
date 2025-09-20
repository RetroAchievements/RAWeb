import { useMutation } from '@tanstack/react-query';
import axios, { type AxiosResponse } from 'axios';
import { route } from 'ziggy-js';

export function useResetWebApiKeyMutation() {
  return useMutation({
    mutationFn: () => {
      return axios.delete<unknown, AxiosResponse<{ newKey: string }>>(
        route('api.settings.keys.web.destroy'),
      );
    },
  });
}

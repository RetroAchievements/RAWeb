import { useMutation } from '@tanstack/react-query';
import axios, { type AxiosResponse } from 'axios';
import { route } from 'ziggy-js';

export function useResetConnectApiKeyMutation() {
  return useMutation({
    mutationFn: () =>
      axios.delete<unknown, AxiosResponse<{ newKey: string }>>(
        route('api.settings.keys.connect.destroy'),
      ),
  });
}

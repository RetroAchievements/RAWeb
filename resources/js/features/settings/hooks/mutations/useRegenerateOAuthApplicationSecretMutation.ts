import { useMutation } from '@tanstack/react-query';
import axios from 'axios';
import { route } from 'ziggy-js';

interface Variables {
  clientId: string;
}

export function useRegenerateOAuthApplicationSecretMutation() {
  return useMutation({
    mutationFn: ({ clientId }: Variables) => {
      return axios.post<App.Data.OAuthClientCredentials>(
        route('api.settings.applications.regenerate-secret', { client: clientId }),
      );
    },
  });
}

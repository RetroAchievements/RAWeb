import { router } from '@inertiajs/react';
import { useMutation } from '@tanstack/react-query';
import axios from 'axios';
import { route } from 'ziggy-js';

interface Variables {
  clientId: string;
  payload: {
    name: string;
    redirectUris: string[];
  };
}

export function useUpdateOAuthApplicationMutation() {
  return useMutation({
    mutationFn: ({ clientId, payload }: Variables) => {
      return axios.put<App.Data.OAuthClient>(
        route('api.settings.applications.update', { client: clientId }),
        payload,
      );
    },

    onSuccess: () => {
      router.reload({ only: ['oauthApplications'] });
    },
  });
}

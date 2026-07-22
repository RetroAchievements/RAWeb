import { router } from '@inertiajs/react';
import { useMutation } from '@tanstack/react-query';
import axios from 'axios';
import { route } from 'ziggy-js';

interface Variables {
  payload: {
    enableDeviceFlow: boolean;
    name: string;
    redirectUris: string[];
    type: 'confidential' | 'public';
  };
}

export function useCreateOAuthApplicationMutation() {
  return useMutation({
    mutationFn: ({ payload }: Variables) => {
      return axios.post<App.Data.OAuthClientCredentials>(
        route('api.settings.applications.store'),
        payload,
      );
    },

    onSuccess: () => {
      router.reload({ only: ['oauthApplications'] });
    },
  });
}

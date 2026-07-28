import { router } from '@inertiajs/react';
import { useMutation } from '@tanstack/react-query';
import axios from 'axios';
import { route } from 'ziggy-js';

interface Variables {
  clientId: string;
}

export function useDeactivateOAuthApplicationMutation() {
  return useMutation({
    mutationFn: ({ clientId }: Variables) => {
      return axios.delete(route('api.settings.applications.destroy', { client: clientId }));
    },

    onSuccess: () => {
      router.reload({ only: ['oauthApplications'] });
    },
  });
}

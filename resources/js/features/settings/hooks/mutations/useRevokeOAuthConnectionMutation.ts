import { router } from '@inertiajs/react';
import { useMutation } from '@tanstack/react-query';
import axios from 'axios';
import { route } from 'ziggy-js';

interface Variables {
  clientId: string;
}

export function useRevokeOAuthConnectionMutation() {
  return useMutation({
    mutationFn: ({ clientId }: Variables) => {
      return axios.delete(
        route('api.settings.connected-applications.destroy', {
          client: clientId,
        }),
      );
    },

    onSuccess: () => {
      router.reload({ only: ['connectedOAuthApplications'] });
    },
  });
}

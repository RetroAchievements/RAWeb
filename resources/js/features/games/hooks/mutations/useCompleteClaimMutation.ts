import { router } from '@inertiajs/react';
import { useMutation } from '@tanstack/react-query';
import axios from 'axios';
import { route } from 'ziggy-js';

interface Variables {
  claimId: number;
}

export function useCompleteClaimMutation() {
  return useMutation({
    mutationFn: ({ claimId }: Variables) => {
      const formData = new FormData();
      formData.append('status', 'complete');

      return axios.post<unknown>(
        route('achievement-set-claim.update', { claim: claimId }),
        formData,
        {
          headers: {
            'Content-Type': 'multipart/form-data',
          },
        },
      );
    },

    onSuccess: () => {
      router.reload();
    },
  });
}

import { router } from '@inertiajs/react';
import { useMutation } from '@tanstack/react-query';
import axios from 'axios';
import { route } from 'ziggy-js';

interface Variables {
  claimId: number;
  status: App.Community.Enums.ClaimStatus;
}

export function useUpdateClaimStatusMutation() {
  return useMutation({
    mutationFn: ({ claimId, status }: Variables) => {
      const formData = new FormData();
      formData.append('status', status);

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

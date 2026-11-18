import { router } from '@inertiajs/react';
import { useMutation } from '@tanstack/react-query';
import axios from 'axios';
import { route } from 'ziggy-js';

interface Variables {
  claimId: number;
}

export function useMarkClaimReleaseScheduledMutation() {
  return useMutation({
    mutationFn: ({ claimId }: Variables) =>
      axios.post<unknown>(route('achievement-set-claim.release-scheduled', { claim: claimId })),

    onSuccess: () => {
      router.reload();
    },
  });
}

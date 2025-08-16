import { router } from '@inertiajs/react';
import { useMutation } from '@tanstack/react-query';
import axios from 'axios';
import { route } from 'ziggy-js';

interface Variables {
  gameId: number;
}

export function useCreateClaimMutation() {
  return useMutation({
    mutationFn: ({ gameId }: Variables) =>
      axios.post<unknown>(route('achievement-set-claim.create', { game: gameId })),

    onSuccess: () => {
      router.reload();
    },
  });
}

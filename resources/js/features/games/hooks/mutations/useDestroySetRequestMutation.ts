import { router } from '@inertiajs/react';
import { useMutation } from '@tanstack/react-query';
import axios from 'axios';
import { route } from 'ziggy-js';

interface Variables {
  gameId: number;
}

export function useDestroySetRequestMutation() {
  return useMutation({
    mutationFn: ({ gameId }: Variables) =>
      axios.delete<{ data: App.Platform.Data.GameSetRequestData }>(
        route('api.game.set-request.destroy', { game: gameId }),
      ),

    onSuccess: () => {
      router.reload({ only: ['setRequestData'] });
    },
  });
}

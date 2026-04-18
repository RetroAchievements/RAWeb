import { useMutation } from '@tanstack/react-query';
import axios from 'axios';
import { route } from 'ziggy-js';

interface Variables {
  formData: FormData;
  gameId: number;
}

export function useSubmitGameScreenshotMutation() {
  return useMutation({
    mutationFn: ({ formData, gameId }: Variables) => {
      return axios.post(route('api.game-screenshot.store', { game: gameId }), formData, {
        headers: {
          'Content-Type': 'multipart/form-data',
        },
      });
    },
  });
}

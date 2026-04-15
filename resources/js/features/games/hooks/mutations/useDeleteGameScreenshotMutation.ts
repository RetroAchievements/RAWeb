import { useMutation } from '@tanstack/react-query';
import axios from 'axios';
import { route } from 'ziggy-js';

interface Variables {
  gameId: number;
  gameScreenshotId: number;
}

export function useDeleteGameScreenshotMutation() {
  return useMutation({
    mutationFn: ({ gameId, gameScreenshotId }: Variables) => {
      return axios.delete(
        route('api.game-screenshot.destroy', {
          game: gameId,
          gameScreenshot: gameScreenshotId,
        }),
      );
    },
  });
}

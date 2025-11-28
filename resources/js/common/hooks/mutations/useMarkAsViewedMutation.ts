import { useMutation } from '@tanstack/react-query';
import axios from 'axios';
import { route } from 'ziggy-js';

interface Variables {
  viewableType: string;
  viewableId: number;
}

export function useMarkAsViewedMutation() {
  return useMutation({
    mutationFn: ({ viewableId, viewableType }: Variables) =>
      axios.post(route('api.viewable.store', { viewableId, viewableType })),
  });
}

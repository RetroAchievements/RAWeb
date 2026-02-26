import { useMutation } from '@tanstack/react-query';
import axios from 'axios';
import { route } from 'ziggy-js';

interface Variables {
  payload: {
    eventId: number;
    tierIndex: number | null;
  };
}

export function useUpdateEventAwardTierPreferenceMutation() {
  return useMutation({
    mutationFn: ({ payload }: Variables) =>
      axios.put(route('api.user.event-award-tier-preference.update'), payload),
  });
}

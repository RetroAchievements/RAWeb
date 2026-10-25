import { useMutation } from '@tanstack/react-query';
import axios from 'axios';
import { route } from 'ziggy-js';

interface Variables {
  ticketId: number;
  payload: {
    isHardcore: boolean;
  };
}

export function useAwardReporterUnlockMutation() {
  return useMutation({
    mutationFn: ({ ticketId, payload }: Variables) =>
      axios.post(route('api.ticket.reporter-unlock.store', { ticket: ticketId }), payload),
  });
}

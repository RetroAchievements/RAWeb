import { useMutation } from '@tanstack/react-query';
import axios from 'axios';
import { route } from 'ziggy-js';

interface Variables {
  ticketId: number;
}

export function useClaimTicketMutation() {
  return useMutation({
    mutationFn: ({ ticketId }: Variables) =>
      axios.post(route('api.ticket.claim.store', { ticket: ticketId })),
  });
}

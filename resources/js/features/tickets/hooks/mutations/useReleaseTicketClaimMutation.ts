import { useMutation } from '@tanstack/react-query';
import axios from 'axios';
import { route } from 'ziggy-js';

interface Variables {
  ticketId: number;
}

export function useReleaseTicketClaimMutation() {
  return useMutation({
    mutationFn: ({ ticketId }: Variables) =>
      axios.delete(route('api.ticket.claim.destroy', { ticket: ticketId })),
  });
}

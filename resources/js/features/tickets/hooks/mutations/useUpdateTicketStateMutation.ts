import { useMutation } from '@tanstack/react-query';
import axios from 'axios';
import { route } from 'ziggy-js';

interface Variables {
  ticketId: number;
  payload: {
    action: App.Community.Enums.TicketAction;

    note?: string | null;
  };
}

export function useUpdateTicketStateMutation() {
  return useMutation({
    mutationFn: ({ ticketId, payload }: Variables) =>
      axios.patch(route('api.ticket.state.update', { ticket: ticketId }), payload),
  });
}

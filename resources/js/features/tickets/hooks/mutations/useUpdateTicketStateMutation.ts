import { useMutation } from '@tanstack/react-query';
import axios from 'axios';
import { route } from 'ziggy-js';

interface Variables {
  ticketId: number;
  action: App.Community.Enums.TicketAction;
}

export function useUpdateTicketStateMutation() {
  return useMutation({
    mutationFn: ({ ticketId, action }: Variables) =>
      axios.patch(route('api.ticket.state.update', { ticket: ticketId }), { action }),
  });
}

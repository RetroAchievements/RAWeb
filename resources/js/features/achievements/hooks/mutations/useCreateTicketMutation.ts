import { useMutation } from '@tanstack/react-query';
import axios from 'axios';
import { route } from 'ziggy-js';

interface Variables {
  payload: {
    ticketableModel: 'achievement' | 'leaderboard';
    ticketableId: number;
    mode: 'hardcore' | 'softcore';
    issue: App.Community.Enums.TriggerTicketType;
    description: string;
    emulator: string;
    emulatorVersion: string | null;
    gameHashId: number;

    core?: string;
    extra?: string | Record<string, string>;
  };
}

export function useCreateTicketMutation() {
  return useMutation({
    mutationFn: ({ payload }: Variables) =>
      axios.post<{ message: string; ticketId: string }>(route('api.ticket.store'), payload),
  });
}

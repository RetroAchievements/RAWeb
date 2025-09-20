import { useMutation } from '@tanstack/react-query';
import axios from 'axios';
import { route } from 'ziggy-js';

interface Variables {
  payload: {
    recipient: string;
    title: string;
    body: string;
    senderUserDisplayName: string;
  };
}

export function useCreateMessageThreadMutation() {
  return useMutation({
    mutationFn: ({ payload }: Variables) => {
      return axios.post<{ threadId: number }>(route('api.message.store'), payload);
    },
  });
}

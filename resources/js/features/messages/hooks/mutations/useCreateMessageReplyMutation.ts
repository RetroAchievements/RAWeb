import { useMutation } from '@tanstack/react-query';
import axios from 'axios';
import { route } from 'ziggy-js';

interface Variables {
  payload: {
    thread_id: number;
    body: string;
  };
}

export function useCreateMessageReplyMutation() {
  return useMutation({
    mutationFn: ({ payload }: Variables) => {
      return axios.post(route('api.message.store'), payload);
    },
  });
}

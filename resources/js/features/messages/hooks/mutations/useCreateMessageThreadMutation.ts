import { useMutation } from '@tanstack/react-query';
import axios from 'axios';
import { route } from 'ziggy-js';

interface Variables {
  payload: {
    body: string;
    recipient: string;
    senderUserDisplayName: string;
    title: string;

    rId?: number;
    rType?: App.Community.Enums.ModerationReportableType;
  };
}

export function useCreateMessageThreadMutation() {
  return useMutation({
    mutationFn: ({ payload }: Variables) => {
      return axios.post<{ threadId: number }>(route('api.message.store'), payload);
    },
  });
}

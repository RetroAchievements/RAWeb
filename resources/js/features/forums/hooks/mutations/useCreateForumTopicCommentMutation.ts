import { useMutation } from '@tanstack/react-query';
import axios from 'axios';
import { route } from 'ziggy-js';

interface Variables {
  topic: number;
  payload: {
    body: string;

    postAsUserId?: number | null;
  };
}

export function useCreateForumTopicCommentMutation() {
  return useMutation({
    mutationFn: ({ topic, payload }: Variables) => {
      return axios.post<{ commentId: number }>(
        route('api.forum-topic-comment.create', { topic }),
        payload,
      );
    },
  });
}

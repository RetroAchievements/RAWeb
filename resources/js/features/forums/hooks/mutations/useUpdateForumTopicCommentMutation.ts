import { useMutation } from '@tanstack/react-query';
import axios from 'axios';
import { route } from 'ziggy-js';

interface Variables {
  comment: number;
  payload: {
    body: string;

    postAsUserId?: number | null;
  };
}

export function useUpdateForumTopicCommentMutation() {
  return useMutation({
    mutationFn: ({ comment, payload }: Variables) => {
      return axios.patch(route('api.forum-topic-comment.update', { comment }), payload);
    },
  });
}

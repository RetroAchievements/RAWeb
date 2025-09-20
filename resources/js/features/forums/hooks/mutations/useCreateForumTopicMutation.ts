import { useMutation } from '@tanstack/react-query';
import axios from 'axios';
import { route } from 'ziggy-js';

interface Variables {
  category: number;
  forum: number;
  payload: {
    title: string;
    body: string;
    postAsUserId: number | null;
  };
}

export function useCreateForumTopicMutation() {
  return useMutation({
    mutationFn: ({ category, forum, payload }: Variables) =>
      axios.post<{ success: boolean; newTopicId: number }>(
        route('api.forum-topic.store', { category, forum }),
        payload,
      ),
  });
}

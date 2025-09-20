import { useMutation } from '@tanstack/react-query';
import axios from 'axios';
import { route } from 'ziggy-js';

interface Variables {
  topic: number;
}

export function useDeleteForumTopicMutation() {
  return useMutation({
    mutationFn: ({ topic }: Variables) => axios.delete(route('api.forum-topic.destroy', { topic })),
  });
}

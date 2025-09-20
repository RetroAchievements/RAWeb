import { useMutation } from '@tanstack/react-query';
import axios from 'axios';
import { route } from 'ziggy-js';

interface Variables {
  topic: number;
}

export function useToggleLockForumTopicMutation() {
  return useMutation({
    mutationFn: ({ topic }: Variables) =>
      axios.post(route('api.forum-topic.toggle-lock', { topic })),
  });
}

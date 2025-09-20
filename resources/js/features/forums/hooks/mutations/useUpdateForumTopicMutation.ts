import { useMutation } from '@tanstack/react-query';
import axios from 'axios';
import { route } from 'ziggy-js';

interface Variables {
  topic: number;
  payload: {
    title: string;
  };
}

export function useUpdateForumTopicMutation() {
  return useMutation({
    mutationFn: ({ topic, payload }: Variables) => {
      return axios.put(route('api.forum-topic.update', { topic }), payload);
    },
  });
}

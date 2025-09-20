import { useMutation } from '@tanstack/react-query';
import axios from 'axios';
import { route } from 'ziggy-js';

interface Variables {
  topic: number;
  payload: {
    permissions: number;
  };
}

export function useGateForumTopicMutation() {
  return useMutation({
    mutationFn: ({ topic, payload }: Variables) =>
      axios.put(route('api.forum-topic.gate', { topic }), payload),
  });
}

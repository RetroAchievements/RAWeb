import { router } from '@inertiajs/react';
import { useMutation } from '@tanstack/react-query';
import axios from 'axios';
import { route } from 'ziggy-js';

export function useCreateOfficialForumTopicMutation() {
  return useMutation({
    mutationFn: (variables: { gameId: number }) =>
      axios.post<{ success: boolean; topicId: number }>(
        route('api.game.forum-topic.create', { game: variables.gameId }),
      ),

    onSuccess: ({ data }) => {
      router.visit(route('forum-topic.show', { topic: data.topicId }));
    },
  });
}

import { useMutation } from '@tanstack/react-query';
import axios from 'axios';

export function useCreateOfficialForumTopicMutation() {
  return useMutation({
    mutationFn: (variables: { gameId: number }) =>
      axios.post<{ success: boolean; topicId: number }>(
        route('api.game.forum-topic.create', { game: variables.gameId }),
      ),

    onSuccess: ({ data }) => {
      window.location.assign(`/viewtopic.php?t=${data.topicId}`);
    },
  });
}

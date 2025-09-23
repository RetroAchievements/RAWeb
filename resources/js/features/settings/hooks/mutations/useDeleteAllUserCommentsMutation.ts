import { useMutation } from '@tanstack/react-query';
import axios from 'axios';
import { route } from 'ziggy-js';

interface Variables {
  userId: number;
}

export function useDeleteAllUserCommentsMutation() {
  return useMutation({
    mutationFn: ({ userId }: Variables) => {
      return axios.delete(route('user.comment.destroyAll', userId));
    },
  });
}

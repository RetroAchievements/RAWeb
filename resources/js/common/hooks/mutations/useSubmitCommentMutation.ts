import { useMutation } from '@tanstack/react-query';
import axios from 'axios';

interface Variables {
  route: string;
  payload: {
    commentableId: string | number;
    commentableType: App.Community.Enums.CommentableType;
    body: string;
  };
}

export function useSubmitCommentMutation() {
  return useMutation({
    mutationFn: ({ route, payload }: Variables) => {
      return axios.post(route, payload);
    },
  });
}

import { router } from '@inertiajs/react';
import { useMutation } from '@tanstack/react-query';
import axios from 'axios';
import { route } from 'ziggy-js';

interface Payload {
  displayName: string;
  isAuthorized: boolean;
}

export function useUpdateUserForumPermissionsMutation() {
  return useMutation({
    mutationFn: (payload: Payload) =>
      axios.put(route('api.user.forum-permissions.update'), payload),

    onSuccess: () => {
      router.reload();
    },
  });
}

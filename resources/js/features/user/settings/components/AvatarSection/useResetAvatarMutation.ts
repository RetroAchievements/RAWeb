import { useMutation } from '@tanstack/react-query';
import axios from 'axios';
import { route } from 'ziggy-js';

export function useResetAvatarMutation() {
  return useMutation({
    mutationFn: () => axios.delete(route('user.avatar.destroy')),
  });
}

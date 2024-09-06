import { useMutation } from '@tanstack/react-query';
import axios from 'axios';

export function useResetAvatarMutation() {
  return useMutation({
    mutationFn: () => axios.delete(route('user.avatar.destroy')),
  });
}

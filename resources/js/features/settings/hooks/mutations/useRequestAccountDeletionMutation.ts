import { useMutation } from '@tanstack/react-query';
import axios from 'axios';
import { route } from 'ziggy-js';

export function useRequestAccountDeletionMutation() {
  return useMutation({
    mutationFn: () => axios.post(route('api.user.delete-request.store')),
  });
}

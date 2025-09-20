import { useMutation } from '@tanstack/react-query';
import axios from 'axios';
import { route } from 'ziggy-js';

export function useCancelAccountDeletionMutation() {
  return useMutation({
    mutationFn: () => axios.delete(route('api.user.delete-request.destroy')),
  });
}

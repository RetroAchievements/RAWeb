import { useMutation } from '@tanstack/react-query';
import axios from 'axios';
import { route } from 'ziggy-js';

export function useManageAccountDeletion() {
  const cancelDeleteMutation = useMutation({
    mutationFn: () => axios.delete(route('user.delete-request.destroy')),
  });

  const requestDeleteMutation = useMutation({
    mutationFn: () => axios.post(route('user.delete-request.store')),
  });

  return { cancelDeleteMutation, requestDeleteMutation };
}

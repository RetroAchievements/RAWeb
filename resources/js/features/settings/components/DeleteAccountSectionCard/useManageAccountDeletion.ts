import { useMutation } from '@tanstack/react-query';
import axios from 'axios';

export function useManageAccountDeletion() {
  const cancelDeleteMutation = useMutation({
    mutationFn: () => axios.delete(route('api.user.delete-request.destroy')),
  });

  const requestDeleteMutation = useMutation({
    mutationFn: () => axios.post(route('api.user.delete-request.store')),
  });

  return { cancelDeleteMutation, requestDeleteMutation };
}

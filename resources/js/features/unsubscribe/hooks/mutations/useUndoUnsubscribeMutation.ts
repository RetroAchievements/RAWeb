import { useMutation } from '@tanstack/react-query';
import axios from 'axios';
import { route } from 'ziggy-js';

export function useUndoUnsubscribeMutation() {
  return useMutation({
    mutationFn: (token: string) => axios.post(route('api.unsubscribe.undo', { token })),
  });
}

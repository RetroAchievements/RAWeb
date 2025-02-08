import { useMutation } from '@tanstack/react-query';
import axios from 'axios';

export function useDeleteMessageThreadMutation() {
  return useMutation({
    mutationFn: (messageThread: App.Community.Data.MessageThread) =>
      axios.delete(route('api.message-thread.destroy', { messageThread })),
  });
}

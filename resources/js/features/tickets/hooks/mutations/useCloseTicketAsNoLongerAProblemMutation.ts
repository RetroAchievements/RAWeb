import { useMutation } from '@tanstack/react-query';
import axios from 'axios';

export function useCloseTicketAsNoLongerAProblemMutation() {
  return useMutation({
    mutationFn: (signedCloseUrl: string) => axios.post(signedCloseUrl),
  });
}

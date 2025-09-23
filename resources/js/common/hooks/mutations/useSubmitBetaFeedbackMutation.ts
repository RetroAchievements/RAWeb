import { useMutation } from '@tanstack/react-query';
import axios from 'axios';
import { route } from 'ziggy-js';

interface Variables {
  payload: {
    betaName: string;
    rating: number;

    positiveFeedback?: string;
    negativeFeedback?: string;
  };
}

export function useSubmitBetaFeedbackMutation() {
  return useMutation({
    mutationFn: ({ payload }: Variables) => axios.post(route('api.beta-feedback.store'), payload),
  });
}

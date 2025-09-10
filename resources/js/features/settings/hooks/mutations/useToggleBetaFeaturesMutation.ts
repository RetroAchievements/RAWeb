import { useMutation } from '@tanstack/react-query';
import axios from 'axios';
import { route } from 'ziggy-js';

export function useToggleBetaFeaturesMutation() {
  return useMutation({
    mutationFn: () => axios.put(route('api.settings.beta-features.toggle')),
  });
}

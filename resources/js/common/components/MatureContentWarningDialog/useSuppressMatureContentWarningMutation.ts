import { useMutation } from '@tanstack/react-query';
import axios from 'axios';
import { route } from 'ziggy-js';

export function useSuppressMatureContentWarningMutation() {
  return useMutation({
    mutationFn: () =>
      axios.patch(route('api.settings.preferences.suppress-mature-content-warning')),
  });
}

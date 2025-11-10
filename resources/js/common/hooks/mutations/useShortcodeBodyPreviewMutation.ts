import { useMutation } from '@tanstack/react-query';
import axios from 'axios';
import { route } from 'ziggy-js';

import type { ShortcodeBodyPreviewMutationResponse } from '@/common/models';

export function useShortcodeBodyPreviewMutation() {
  return useMutation({
    mutationFn: (body: string) =>
      axios.post<ShortcodeBodyPreviewMutationResponse>(route('api.shortcode-body.preview'), {
        body,
      }),
  });
}

import { useMutation } from '@tanstack/react-query';
import axios from 'axios';
import { route } from 'ziggy-js';

import type {
  DynamicShortcodeEntities,
  ShortcodeBodyPreviewMutationResponse,
} from '@/common/models';

export function useShortcodeBodyPreviewMutation() {
  return useMutation({
    mutationFn: (payload: DynamicShortcodeEntities) =>
      axios.post<ShortcodeBodyPreviewMutationResponse>(
        route('api.shortcode-body.preview'),
        payload,
      ),
  });
}

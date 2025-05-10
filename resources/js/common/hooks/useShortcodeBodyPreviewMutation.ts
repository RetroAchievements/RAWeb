import { useMutation } from '@tanstack/react-query';
import axios from 'axios';
import { route } from 'ziggy-js';

import type { DynamicShortcodeEntities } from '@/common/models';

export interface ShortcodeBodyPreviewMutationResponse {
  achievements: App.Platform.Data.Achievement[];
  games: App.Platform.Data.Game[];
  hubs: App.Platform.Data.GameSet[];
  events: App.Platform.Data.Event[];
  tickets: App.Platform.Data.Ticket[];
  users: App.Data.User[];
}

export function useShortcodeBodyPreviewMutation() {
  return useMutation({
    mutationFn: (payload: DynamicShortcodeEntities) =>
      axios.post<ShortcodeBodyPreviewMutationResponse>(
        route('api.shortcode-body.preview'),
        payload,
      ),
  });
}

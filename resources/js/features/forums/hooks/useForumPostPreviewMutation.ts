import { useMutation } from '@tanstack/react-query';
import axios from 'axios';

import type { DynamicShortcodeEntities } from '../models';

export interface ForumPostPreviewMutationResponse {
  achievements: App.Platform.Data.Achievement[];
  games: App.Platform.Data.Game[];
  hubs: App.Platform.Data.GameSet[];
  tickets: App.Platform.Data.Ticket[];
  users: App.Data.User[];
}

export function useForumPostPreviewMutation() {
  return useMutation({
    mutationFn: (payload: DynamicShortcodeEntities) =>
      axios.post<ForumPostPreviewMutationResponse>(
        route('api.forum-topic-comment.preview'),
        payload,
      ),
  });
}

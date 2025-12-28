import { useMutation } from '@tanstack/react-query';
import axios from 'axios';
import { route } from 'ziggy-js';

interface Variables {
  comment: App.Community.Data.Comment & { targetUserDisplayName?: string };
}

export function useDeleteCommentMutation() {
  return useMutation({
    mutationFn: ({ comment }: Variables) => {
      return axios.delete(buildDeleteRoute(comment));
    },
  });
}

function buildDeleteRoute({
  commentableId,
  commentableType,
  id,
  targetUserDisplayName = '',
}: Variables['comment']): string {
  const commentableTypeRouteMap: Record<App.Community.Enums.CommentableType, string> = {
    'achievement.comment': route('api.achievement.comment.destroy', {
      achievement: commentableId,
      comment: id,
    }),

    'trigger.ticket.comment': 'TODO',

    'forum.comment': 'TODO',

    'game.comment': route('api.game.comment.destroy', { game: commentableId, comment: id }),

    'game-hash.comment': route('api.game.hashes.comment.destroy', {
      game: commentableId,
      comment: id,
    }),

    'game-modification.comment': route('api.game.modification-comment.destroy', {
      game: commentableId,
      comment: id,
    }),

    'leaderboard.comment': route('api.leaderboard.comment.destroy', {
      leaderboard: commentableId,
      comment: id,
    }),

    'achievement-set-claim.comment': route('api.game.claims.comment.destroy', {
      game: commentableId,
      comment: id,
    }),

    'user.comment': route('api.user.comment.destroy', {
      user: targetUserDisplayName,
      comment: id,
    }),

    'user-activity.comment': 'TODO',

    'user-moderation.comment': route('api.user.moderation-comment.destroy', {
      user: targetUserDisplayName,
      comment: id,
    }),
  };

  return commentableTypeRouteMap[commentableType];
}

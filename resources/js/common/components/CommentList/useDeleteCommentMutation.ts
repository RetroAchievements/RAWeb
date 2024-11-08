import { useMutation } from '@tanstack/react-query';
import axios from 'axios';

import { ArticleType } from '@/common/utils/generatedAppConstants';

type MutationFnProps = App.Community.Data.Comment & { targetUserDisplayName?: string };

export function useDeleteCommentMutation() {
  return useMutation({
    mutationFn: (comment: MutationFnProps) => {
      return axios.delete(buildDeleteRoute(comment));
    },
  });
}

function buildDeleteRoute({
  commentableId,
  commentableType,
  id,
  targetUserDisplayName = '',
}: MutationFnProps): string {
  const commentableTypeRouteMap: Record<number, string> = {
    [ArticleType.Achievement]: route('api.achievement.comment.destroy', {
      achievement: commentableId,
      comment: id,
    }),

    [ArticleType.AchievementTicket]: 'TODO',

    [ArticleType.Forum]: 'TODO',

    [ArticleType.Game]: route('api.game.comment.destroy', { game: commentableId, comment: id }),

    [ArticleType.GameHash]: 'TODO',

    [ArticleType.GameModification]: 'TODO',

    [ArticleType.Leaderboard]: route('api.leaderboard.comment.destroy', {
      leaderboard: commentableId,
      comment: id,
    }),

    [ArticleType.News]: 'TODO',

    [ArticleType.SetClaim]: 'TODO',

    [ArticleType.User]: route('api.user.comment.destroy', {
      user: targetUserDisplayName,
      comment: id,
    }),

    [ArticleType.UserModeration]: 'TODO',
  };

  return commentableTypeRouteMap[commentableType];
}

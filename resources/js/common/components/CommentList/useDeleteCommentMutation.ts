import { useMutation } from '@tanstack/react-query';
import axios from 'axios';

import { ArticleType } from '@/common/utils/generatedAppConstants';

export function useDeleteCommentMutation() {
  return useMutation({
    mutationFn: (comment: App.Community.Data.Comment) => {
      return axios.delete(buildDeleteRoute(comment));
    },
  });
}

function buildDeleteRoute({
  commentableId,
  commentableType,
  id,
}: App.Community.Data.Comment): string {
  const commentableTypeRouteMap: Record<number, string> = {
    [ArticleType.Achievement]: 'TODO',
    [ArticleType.AchievementTicket]: 'TODO',
    [ArticleType.Forum]: 'TODO',
    [ArticleType.Game]: route('api.game.comment.destroy', { game: commentableId, comment: id }),
    [ArticleType.GameHash]: 'TODO',
    [ArticleType.GameModification]: 'TODO',
    [ArticleType.Leaderboard]: 'TODO',
    [ArticleType.News]: 'TODO',
    [ArticleType.SetClaim]: 'TODO',
    [ArticleType.User]: 'TODO',
    [ArticleType.UserModeration]: 'TODO',
  };

  return commentableTypeRouteMap[commentableType];
}

import { useMemo } from 'react';

import { usePageProps } from '@/common/hooks/usePageProps';

import { getAchievementSetPointsStats } from '../utils/getAchievementSetPointsStats';

export function useGameMetaDescription(): { description: string; noindex: boolean } {
  const { backingGame, game, targetAchievementSetId, isViewingPublishedAchievements } =
    usePageProps<App.Platform.Data.GameShowPageProps>();

  return useMemo(() => {
    /** Viewing unpublished achievements */
    if (!isViewingPublishedAchievements) {
      const setsToShowContent = targetAchievementSetId
        ? game.gameAchievementSets!.filter(
            (gas) => gas.achievementSet.id === targetAchievementSetId,
          )
        : game.gameAchievementSets!;

      const allAchievements = setsToShowContent[0].achievementSet.achievements;

      if (allAchievements.length === 0) {
        return {
          description: `There are no unpublished achievements for ${backingGame.title} (${game.system!.name}).`,
          noindex: true,
        };
      }

      const { pointsTotal } = getAchievementSetPointsStats(allAchievements);

      return {
        description: `There are ${allAchievements.length} unpublished achievements worth ${pointsTotal.toLocaleString()} points. ${backingGame.title} for ${game.system!.name} - explore and compete on this classic game at RetroAchievements.`,
        noindex: true,
      };
    }

    /** Viewing published achievements */
    if (!backingGame.achievementsPublished) {
      return {
        description: `No achievements have been published yet for ${backingGame.title}. Join RetroAchievements to request achievements for this game and unlock achievements on 10,000+ other classic games.`,
        noindex: false,
      };
    }

    return {
      description: `There are ${backingGame.achievementsPublished} achievements worth ${backingGame.pointsTotal!.toLocaleString()} points. ${backingGame.title} for ${game.system!.name} - explore and compete on this classic game at RetroAchievements.`,
      noindex: false,
    };
  }, [
    backingGame.achievementsPublished,
    backingGame.pointsTotal,
    backingGame.title,
    game.gameAchievementSets,
    game.system,
    isViewingPublishedAchievements,
    targetAchievementSetId,
  ]);
}

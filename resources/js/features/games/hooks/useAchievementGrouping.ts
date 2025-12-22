import { useMemo } from 'react';

import { bucketAchievementsByGroup } from '@/features/games/utils/bucketAchievementsByGroup';

interface UseAchievementGroupingProps {
  allAchievements: App.Platform.Data.Achievement[];
  ssrLimitedAchievements: App.Platform.Data.Achievement[];

  rawAchievementGroups?: App.Platform.Data.AchievementSetGroup[];
}

export function useAchievementGrouping({
  allAchievements,
  ssrLimitedAchievements,
  rawAchievementGroups,
}: UseAchievementGroupingProps) {
  const achievementGroups = useMemo(() => rawAchievementGroups ?? [], [rawAchievementGroups]);

  const hasGroups = achievementGroups.length > 0;

  const bucketedAchievements = useMemo(() => {
    if (!hasGroups) {
      return null;
    }

    return bucketAchievementsByGroup(ssrLimitedAchievements, achievementGroups);
  }, [ssrLimitedAchievements, achievementGroups, hasGroups]);

  /**
   * Compute the count of ungrouped achievements by subtracting the sum
   * of all group counts from the total. This ensures correct counts
   * even during SSR when we're only rendering a subset of achievements.
   */
  const ungroupedAchievementCount = useMemo(() => {
    if (!hasGroups) {
      return 0;
    }

    let totalInGroups = 0;
    for (const group of achievementGroups) {
      totalInGroups += group.achievementCount;
    }

    return allAchievements.length - totalInGroups;
  }, [allAchievements.length, achievementGroups, hasGroups]);

  return {
    achievementGroups,
    bucketedAchievements,
    hasGroups,
    ungroupedAchievementCount,
  };
}

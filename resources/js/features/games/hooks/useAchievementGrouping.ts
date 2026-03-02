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
  const achievementGroups = rawAchievementGroups ?? [];

  const hasGroups = achievementGroups.length > 0;

  const bucketedAchievements = hasGroups
    ? bucketAchievementsByGroup(ssrLimitedAchievements, achievementGroups)
    : null;

  /**
   * Compute the count of ungrouped achievements by subtracting the sum
   * of all group counts from the total. This ensures correct counts
   * even during SSR when we're only rendering a subset of achievements.
   */
  let ungroupedAchievementCount = 0;
  if (hasGroups) {
    let totalInGroups = 0;
    for (const group of achievementGroups) {
      totalInGroups += group.achievementCount;
    }

    ungroupedAchievementCount = allAchievements.length - totalInGroups;
  }

  return {
    achievementGroups,
    bucketedAchievements,
    hasGroups,
    ungroupedAchievementCount,
  };
}

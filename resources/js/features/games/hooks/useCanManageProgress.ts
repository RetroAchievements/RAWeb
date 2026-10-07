import { usePageProps } from '@/common/hooks/usePageProps';

export function useCanManageProgress(achievementSetId: number, achievements: App.Platform.Data.Achievement[]) {
  const { playerAchievementSets } = usePageProps<App.Platform.Data.GameShowPageProps>();

  const playerAchievementSet = playerAchievementSets?.[achievementSetId] ?? null;

  const hasUnlocks = achievements.some((ach) => ach.unlockedAt);

  const hasCompletion = playerAchievementSet?.completedAt || playerAchievementSet?.completedHardcoreAt;

  return { playerAchievementSet, canManageProgress: !!(hasUnlocks || hasCompletion) };
}

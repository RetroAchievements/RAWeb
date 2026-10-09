export function getCanManageProgress(
  achievementSetId: number | null,
  achievements: App.Platform.Data.Achievement[],
  playerAchievementSets: Record<number, App.Platform.Data.PlayerAchievementSet>
) {
  const hasUnlocks = achievements.some((ach) => ach.unlockedAt);

  const playerAchievementSet = playerAchievementSets
    ? (achievementSetId
      ? playerAchievementSets[achievementSetId] ?? null
      : Object.values(playerAchievementSets)[0] ?? null)
    : null;

  const hasCompletion = playerAchievementSet?.completedAt || playerAchievementSet?.completedHardcoreAt;

  return !!(hasUnlocks || hasCompletion);
}

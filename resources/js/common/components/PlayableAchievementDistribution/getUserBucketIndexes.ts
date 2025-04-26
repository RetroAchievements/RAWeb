export function getUserBucketIndexes(
  buckets: App.Platform.Data.PlayerAchievementChartBucket[],
  playerGame: App.Platform.Data.PlayerGame | null,
): Partial<{ userHardcoreIndex: number; userSoftcoreIndex: number }> {
  if (!playerGame) {
    return {};
  }

  const result: Partial<{ userHardcoreIndex: number; userSoftcoreIndex: number }> = {};

  const { achievementsUnlockedHardcore, achievementsUnlocked } = playerGame;

  if (achievementsUnlockedHardcore) {
    const index = buckets.findIndex(
      (b) => achievementsUnlockedHardcore >= b.start && achievementsUnlockedHardcore <= b.end,
    );

    if (index !== -1) {
      result.userHardcoreIndex = index;
    }
  }

  if (achievementsUnlocked && achievementsUnlocked !== achievementsUnlockedHardcore) {
    const index = buckets.findIndex(
      (b) => achievementsUnlocked >= b.start && achievementsUnlocked <= b.end,
    );

    if (index !== -1) {
      result.userSoftcoreIndex = index;
    }
  }

  return result;
}

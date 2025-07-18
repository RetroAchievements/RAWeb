export function filterAchievements(
  achievements: App.Platform.Data.Achievement[],
  filters: { showLockedOnly: boolean; showMissableOnly: boolean },
): App.Platform.Data.Achievement[] {
  const { showLockedOnly, showMissableOnly } = filters;

  return achievements.filter((achievement) => {
    // Check if achievement is locked (not unlocked).
    const isLocked = !achievement.unlockedAt && !achievement.unlockedHardcoreAt;

    // If showLockedOnly is true, only include locked achievements.
    if (showLockedOnly && !isLocked) {
      return false;
    }

    // If showMissableOnly is true, only include missable achievements.
    const isMissable = achievement.type === 'missable';
    if (showMissableOnly && !isMissable) {
      return false;
    }

    // If we made it here, the achievement passes all active filters.
    return true;
  });
}

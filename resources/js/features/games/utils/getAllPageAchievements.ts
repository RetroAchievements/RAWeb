export function getAllPageAchievements(
  gameAchievementSets: App.Platform.Data.GameAchievementSet[],
  targetAchievementSetId?: number | null,
): App.Platform.Data.Achievement[] {
  const setsToShow = targetAchievementSetId
    ? gameAchievementSets.filter((gas) => gas.achievementSet.id === targetAchievementSetId)
    : gameAchievementSets;

  return setsToShow.flatMap((s) => s.achievementSet.achievements);
}

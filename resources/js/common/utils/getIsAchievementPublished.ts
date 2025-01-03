export function getIsAchievementPublished(achievement: App.Platform.Data.Achievement): boolean {
  return achievement.flags === 3;
}

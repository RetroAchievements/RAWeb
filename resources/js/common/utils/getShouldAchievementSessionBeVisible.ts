export function getShouldAchievementSessionBeVisible(
  session: App.Platform.Data.PlayerGameActivitySession,
  isOnlyShowingAchievementSessions: boolean,
): boolean {
  const hasAchievements = session.events.some((event) => event.achievement);

  return !isOnlyShowingAchievementSessions || hasAchievements;
}

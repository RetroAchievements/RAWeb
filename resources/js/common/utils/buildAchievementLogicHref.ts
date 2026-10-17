/**
 * For performance reasons, Filament routes are not registered with the Ziggy `route()` function.
 */
export function buildAchievementLogicHref(achievementId: number, version: number): string {
  return `/manage/achievements/${achievementId}/logic?version=${version}`;
}

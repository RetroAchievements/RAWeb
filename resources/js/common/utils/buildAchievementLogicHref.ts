/**
 * For performance reasons, Filament routes are not registered with the Ziggy `route()` function.
 */
export function buildAchievementLogicHref(achievementId: number, version?: number): string {
  const href = `/manage/achievements/${achievementId}/logic`;

  return version === undefined ? href : `${href}?version=${version}`;
}

export function createVirtualAward(
  event: App.Platform.Data.Event,
  numMasters: number,
): App.Platform.Data.EventAward | null {
  if (!event.eventAchievements || !event.legacyGame?.badgeUrl || !event.legacyGame.title) {
    return null;
  }

  const pointedEventAchievements = event.eventAchievements.filter((ea) => !!ea.achievement?.points);

  if (!pointedEventAchievements?.length) {
    return null;
  }

  let totalPoints = 0;
  for (const ea of pointedEventAchievements) {
    // This will always be truthy due to the filter above.
    // TS isn't smart enough to infer .filter()'s implied type constraint.
    totalPoints += ea.achievement!.points!;
  }

  return {
    id: 0,
    badgeUrl: event.legacyGame.badgeUrl,
    earnedAt: null,
    eventId: event.id,
    label: event.legacyGame.title,
    pointsRequired: totalPoints,
    tierIndex: 0,
    badgeCount: numMasters,
  };
}

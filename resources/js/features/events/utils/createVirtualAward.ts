export function createVirtualAward(
  event: App.Platform.Data.Event,
  numMasters: number,
): App.Platform.Data.EventAward | null {
  if (!event.eventAchievements || !event.legacyGame?.badgeUrl || !event.legacyGame.title) {
    return null;
  }

  const pointedEventAchievements = event.eventAchievements.filter((ea) => !!ea.achievement?.points);

  let totalPoints = 0;
  for (const ea of pointedEventAchievements) {
    // This will always be truthy due to the filter above.
    // TS isn't smart enough to infer .filter()'s implied type constraint.
    totalPoints += ea.achievement!.points!;
  }

  const hasEarned = pointedEventAchievements.every((ea) => !!ea.achievement?.unlockedHardcoreAt);

  let earnedAt: string | null = null;
  if (hasEarned) {
    // Find the most recent unlock date.
    earnedAt = pointedEventAchievements
      .map((ea) => ea.achievement!.unlockedHardcoreAt!)
      .sort()
      .pop()!;
  }

  return {
    id: 0,
    earnedAt,
    badgeUrl: event.legacyGame.badgeUrl,
    eventId: event.id,
    label: event.legacyGame.title,
    pointsRequired: totalPoints,
    tierIndex: 0,
    badgeCount: numMasters,
  };
}

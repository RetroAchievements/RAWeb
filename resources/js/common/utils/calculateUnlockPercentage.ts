/**
 * Calculates the unlock percentage for an achievement.
 * When prioritizing hardcore stats, it calculates hardcore unlocks as a percentage of total players.
 * Otherwise, it uses the pre-calculated unlock percentage from the achievement.
 */
export function calculateUnlockPercentage(
  shouldPrioritizeHardcoreStats: boolean,
  unlocksHardcoreTotal: number,
  playersTotal: number | null,
  defaultUnlockPercentage?: string | number | null,
): number {
  if (shouldPrioritizeHardcoreStats) {
    return playersTotal && playersTotal > 0 ? unlocksHardcoreTotal / playersTotal : 0;
  }

  return defaultUnlockPercentage ? Number(defaultUnlockPercentage) : 0;
}

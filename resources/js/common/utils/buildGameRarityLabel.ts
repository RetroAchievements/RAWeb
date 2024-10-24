/**
 * Builds a formatted rarity label for a game based on its total points
 * and weighted points. The rarity is represented as a multiplier (eg: "×3.52").
 *
 * If there are no points for the game, the function returns null.
 */
export function buildGameRarityLabel(
  pointsTotal: App.Platform.Data.Game['pointsTotal'],
  pointsWeighted: App.Platform.Data.Game['pointsWeighted'],
): string | null {
  if (!pointsTotal) {
    return null;
  }

  const ratio = (pointsWeighted ?? 0) / pointsTotal;

  // "×3.52"
  return `×${(Math.round((ratio + Number.EPSILON) * 100) / 100).toFixed(2)}`;
}

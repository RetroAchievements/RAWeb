/**
 * Calculates total points and weighted points from an array of achievements.
 * Useful for computing stats when achievement set aggregates aren't pre-calculated.
 */
export function getAchievementSetPointsStats(achievements: App.Platform.Data.Achievement[]): {
  pointsTotal: number;
  pointsWeighted: number;
} {
  const stats = {
    pointsTotal: 0,
    pointsWeighted: 0,
  };

  for (const achievement of achievements) {
    const achievementPoints = achievement.points as number;
    const achievementPointsWeighted = achievement.pointsWeighted as number;

    stats.pointsTotal += achievementPoints;
    stats.pointsWeighted += achievementPointsWeighted;
  }

  return stats;
}

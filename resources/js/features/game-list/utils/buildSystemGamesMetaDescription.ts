export function buildSystemGamesMetaDescription(totalGames: number, systemName: string): string {
  const firstPhrase =
    totalGames >= 10
      ? `Explore ${(Math.floor(totalGames / 100) * 100).toLocaleString()}+ ${systemName} games on RetroAchievements.`
      : `Explore ${totalGames} ${systemName} games on RetroAchievements.`;

  const secondPhrase = 'Track your progress as you beat and master each title.';

  return `${firstPhrase} ${secondPhrase}`;
}

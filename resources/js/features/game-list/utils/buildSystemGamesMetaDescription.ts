export function buildSystemGamesMetaDescription(totalGames: number, systemName: string): string {
  let firstPhrase;

  if (totalGames < 10) {
    // For a games count less than 10, show the exact count.
    firstPhrase = `Explore ${totalGames} ${systemName} games on RetroAchievements.`;
  } else if (totalGames < 100) {
    // For a games count between 10-99, round down to the nearest ten and add a plus sign.
    const roundedGames = Math.floor(totalGames / 10) * 10;
    firstPhrase = `Explore ${roundedGames}+ ${systemName} games on RetroAchievements.`;
  } else {
    // For a games count of 100+, round down to nearest hundred and add a plus sign.
    const roundedGames = Math.floor(totalGames / 100) * 100;
    firstPhrase = `Explore ${roundedGames.toLocaleString()}+ ${systemName} games on RetroAchievements.`;
  }

  const secondPhrase = 'Track your progress as you beat and master each title.';

  return `${firstPhrase} ${secondPhrase}`;
}

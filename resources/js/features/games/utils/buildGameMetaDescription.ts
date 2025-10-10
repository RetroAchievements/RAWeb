export function buildGameMetaDescription(
  game: App.Platform.Data.Game,
  backingGame: App.Platform.Data.Game,
): string {
  if (!backingGame.achievementsPublished) {
    return `No achievements have been created yet for ${backingGame.title}. Join RetroAchievements to request achievements for this game and earn achievements on many other classic games.`;
  }

  return `There are ${backingGame.achievementsPublished} achievements worth ${backingGame.pointsTotal!.toLocaleString()} points. ${backingGame.title} for ${game.system!.name} - explore and compete on this classic game at RetroAchievements.`;
}

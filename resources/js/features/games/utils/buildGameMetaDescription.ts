export function buildGameMetaDescription(game: App.Platform.Data.Game): string {
  if (!game.achievementsPublished) {
    return `No achievements have been created yet for ${game.title}. Join RetroAchievements to request achievements for this game and earn achievements on many other classic games.`;
  }

  return `There are ${game.achievementsPublished} achievements worth ${game.pointsTotal!.toLocaleString()} points. ${game.title} for ${game.system!.name} - explore and compete on this classic game at RetroAchievements.`;
}

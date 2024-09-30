export function getIsEventGame(game: App.Platform.Data.Game): boolean {
  if (!game.system) {
    return false;
  }

  /**
   * @see App\Models\System
   */
  return game.system.id === 101;
}

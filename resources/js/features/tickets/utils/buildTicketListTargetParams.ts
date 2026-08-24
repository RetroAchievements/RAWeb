type TicketListTarget = Pick<App.Platform.Data.TicketListPageProps, 'achievement' | 'game'>;

export function buildTicketListTargetParams(target: TicketListTarget): Record<string, number> {
  if (target.game) {
    return { game: target.game.id };
  }

  if (target.achievement) {
    return { achievement: target.achievement.id };
  }

  return {};
}

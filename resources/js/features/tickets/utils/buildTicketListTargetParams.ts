type TicketListTarget = Pick<
  App.Platform.Data.TicketListPageProps,
  'achievement' | 'game' | 'user'
>;

export function buildTicketListTargetParams(target: TicketListTarget): Record<string, number> {
  if (target.game) {
    return { game: target.game.id };
  }

  if (target.achievement) {
    return { achievement: target.achievement.id };
  }

  if (target.user?.id) {
    return { user: target.user.id };
  }

  return {};
}

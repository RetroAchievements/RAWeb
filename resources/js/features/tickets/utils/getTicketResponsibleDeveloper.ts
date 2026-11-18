export function getTicketResponsibleDeveloper({
  achievement,
  leaderboard,
  maintainer,
}: Pick<App.Platform.Data.TicketShowPageProps, 'achievement' | 'leaderboard' | 'maintainer'>):
  | App.Data.User
  | null
  | undefined {
  if (leaderboard) {
    return leaderboard.developer;
  }

  return maintainer ?? achievement?.developer;
}

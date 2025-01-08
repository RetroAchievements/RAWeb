export function buildInitialDefaultColumnVisibility(
  isUserAuthenticated: boolean,
): Partial<Record<App.Platform.Enums.GameListSortField, boolean>> {
  return {
    hasActiveOrInReviewClaims: false,
    lastUpdated: false,
    numUnresolvedTickets: false,
    numVisibleLeaderboards: false,
    playersTotal: true,
    progress: isUserAuthenticated,
  };
}

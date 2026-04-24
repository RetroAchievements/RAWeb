export function buildInitialDefaultColumnVisibility(
  isUserAuthenticated: boolean,
): Partial<Record<App.Platform.Enums.GameListSortField, boolean>> {
  return {
    beatRatio: false,
    hasActiveOrInReviewClaims: false,
    lastUpdated: false,
    masteryRatio: false,
    medianTimeToBeatHardcore: false,
    medianTimeToMasterHardcore: false,
    numUnresolvedTickets: false,
    numVisibleLeaderboards: false,
    playersTotal: true,
    progress: isUserAuthenticated,
  };
}

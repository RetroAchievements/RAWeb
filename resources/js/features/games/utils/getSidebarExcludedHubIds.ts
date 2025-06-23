export function getSidebarExcludedHubIds(
  hubs: App.Platform.Data.GameSet[],
  seriesHub: App.Platform.Data.SeriesHub | null,
  metaUsedHubIds: number[],
): number[] {
  const filteredHubIds = hubs
    .filter((hub) => {
      // Exclude event hubs.
      if (hub.isEventHub) {
        return true;
      }

      // Exclude the hub that matches the series hub.
      if (seriesHub?.hub && hub.id === seriesHub.hub.id) {
        return true;
      }

      return false;
    })
    .map((hub) => hub.id);

  // Combine with meta hub IDs that are already used.
  return [...metaUsedHubIds, ...filteredHubIds];
}

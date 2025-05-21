import { route } from 'ziggy-js';

export function buildMiscRowElements(
  allGameHubs: App.Platform.Data.GameSet[],
  usedHubIds: Set<number>,
  options: Partial<{ keepPrefixFor: string[] }>,
): Array<{ label: string; hubId?: number; href?: string }> {
  const { keepPrefixFor } = options;

  // Get all hubs that haven't been categorized yet.
  const uncategorizedHubs = allGameHubs.filter((hub) => {
    const title = hub.title!.toLowerCase();

    // Exclude Series hubs and Meta team hubs from misc categorization.
    if (title.includes('series -') || title.includes('meta|')) {
      return false;
    }

    // Only include hubs that haven't been used by other categories.
    return hub.id && !usedHubIds.has(hub.id);
  });

  // Process the uncategorized hubs as Misc. items.
  const miscItems = uncategorizedHubs.map((hub) => {
    let label = hub.title!;

    // Remove leading/trailing brackets if they're present.
    // In a future state, they probably won't be present anymore.
    if (label.startsWith('[') && label.endsWith(']')) {
      label = label.slice(1, -1);
    }

    // Check if this hub should keep its prefix.
    const shouldKeepPrefix = keepPrefixFor?.some((prefix) =>
      label.toLowerCase().startsWith(prefix.toLowerCase() + ' -'),
    );

    // If it has a dash pattern and we shouldn't keep the prefix, take everything after the dash.
    if (!shouldKeepPrefix) {
      const dashIndex = label.indexOf(' - ');
      if (dashIndex !== -1) {
        label = label.substring(dashIndex + 3);
      }
    }

    return {
      label,
      hubId: hub.id,
      href: route('hub.show', hub.id),
    };
  });

  return miscItems.sort((a, b) => a.label.localeCompare(b.label));
}

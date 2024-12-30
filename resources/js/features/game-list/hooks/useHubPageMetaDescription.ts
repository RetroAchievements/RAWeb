import { usePage } from '@inertiajs/react';

import { usePageProps } from '@/common/hooks/usePageProps';
import { cleanHubTitle } from '@/common/utils/cleanHubTitle';

export function useHubPageMetaDescription() {
  const page = usePage();

  const { hub, relatedHubs, paginatedGameListEntries } =
    usePageProps<App.Platform.Data.HubPageProps>();

  if (page.url === '/hubs') {
    return 'Discover our extensive collection of retro game hubs, featuring classic titles organized by platform, genre, series, and more. Find your next game to play in our hand-curated categories.';
  }

  // For titles in related hubs, remove the prefix.
  const cleanedTitle = cleanHubTitle(hub.title!, relatedHubs.length > 0);

  if (relatedHubs.length && !paginatedGameListEntries.total) {
    return `Discover a curated collection of ${cleanedTitle} related content and hubs on RetroAchievements. Browse through our hand-organized categories to find similar games.`;
  }

  if (paginatedGameListEntries.total) {
    return `Explore a collection of ${paginatedGameListEntries.total.toLocaleString()} ${paginatedGameListEntries.total === 1 ? 'classic game' : 'classic games'} in the ${cleanedTitle} hub.`;
  }

  // This only happens with orphaned hubs, which probably generate no SEO juice anyway.
  return `Explore the ${cleanedTitle} hub on RetroAchievements.`;
}

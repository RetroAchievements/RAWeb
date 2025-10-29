import type { PlayableListSortOrder } from '../models';

export function sortLeaderboards(
  leaderboards: App.Platform.Data.Leaderboard[],
  sortOrder: PlayableListSortOrder,
): App.Platform.Data.Leaderboard[] {
  // Create a copy to avoid mutating the original array.
  const sortedLeaderboards = [...leaderboards];

  switch (sortOrder) {
    case 'displayOrder':
    case '-displayOrder': {
      const multiplier = sortOrder === 'displayOrder' ? 1 : -1;

      return sortedLeaderboards.sort((a, b) => {
        // Sort by orderColumn (alias for DisplayOrder).
        const orderDiff = (a.orderColumn as number) - (b.orderColumn as number);
        if (orderDiff !== 0) {
          return orderDiff * multiplier;
        }

        // If orderColumn is the same, sort by ID as a fallback.
        return ((a.id as number) - (b.id as number)) * multiplier;
      });
    }

    case 'title':
    case '-title': {
      const multiplier = sortOrder === 'title' ? 1 : -1;

      return sortedLeaderboards.sort((a, b) => {
        // Sort case-insensitively by title.
        const aTitle = (a.title as string).toLowerCase();
        const bTitle = (b.title as string).toLowerCase();

        return aTitle.localeCompare(bTitle) * multiplier;
      });
    }

    case 'rank':
    case '-rank': {
      const reversed = sortOrder === 'rank';

      const sorted = sortedLeaderboards.sort((a, b) => {
        const aRank = a.userEntry?.rank || (!reversed ? 0 : 9999999); // this isn't ideal
        const bRank = b.userEntry?.rank || (!reversed ? 0 : 9999999);

        return aRank - bRank;
      });

      return reversed ? sorted : sorted.reverse();
    }

    default:
      return sortedLeaderboards;
  }
}

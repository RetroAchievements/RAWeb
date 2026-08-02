import type { PlayableListSortOrder } from '../models';
import { getEventAchievementTimeStatus } from './getEventAchievementTimeStatus';

export function sortAchievements(
  achievements: App.Platform.Data.Achievement[],
  sortOrder: PlayableListSortOrder,
  eventAchievements?: App.Platform.Data.EventAchievement[],
): App.Platform.Data.Achievement[] {
  // Create a copy to avoid mutating the original array.
  const sortedAchievements = [...achievements];

  switch (sortOrder) {
    case 'normal': {
      return sortedAchievements.sort((a, b) => {
        // Only this sort order puts unlocked achievements first. The UI shows it
        // as "Unlocked first". Do not copy these lines into another sort order.
        const aUnlocked = !!a.unlockedAt || !!a.unlockedHardcoreAt;
        const bUnlocked = !!b.unlockedAt || !!b.unlockedHardcoreAt;
        if (aUnlocked !== bUnlocked) {
          return aUnlocked ? -1 : 1;
        }

        // Then sort by orderColumn within each group (unlocked and not unlocked).
        const orderDiff = (a.orderColumn as number) - (b.orderColumn as number);
        if (orderDiff !== 0) {
          return orderDiff;
        }

        // If orderColumn is the same, sort by createdAt.
        const aDate = new Date(a.createdAt as string).valueOf();
        const bDate = new Date(b.createdAt as string).valueOf();

        return aDate - bDate;
      });
    }

    case 'displayOrder':
    case '-displayOrder': {
      const multiplier = sortOrder === 'displayOrder' ? 1 : -1;

      return sortedAchievements.sort((a, b) => {
        // First, sort by orderColumn if it exists.
        const orderDiff = (a.orderColumn as number) - (b.orderColumn as number);
        if (orderDiff !== 0) {
          return orderDiff * multiplier;
        }

        // Then, sort by createdAt if orderColumn is the same.
        const aDate = new Date(a.createdAt as string).valueOf();
        const bDate = new Date(b.createdAt as string).valueOf();

        return (aDate - bDate) * multiplier;
      });
    }

    case 'points':
    case '-points': {
      const multiplier = sortOrder === 'points' ? 1 : -1;

      return sortedAchievements.sort((a, b) => {
        const pointsDiff = (b.points as number) - (a.points as number);
        if (pointsDiff !== 0) {
          return pointsDiff * multiplier;
        }

        // If points are equal, sort by orderColumn.
        const orderDiff = (b.orderColumn as number) - (a.orderColumn as number);
        if (orderDiff !== 0) {
          return orderDiff * multiplier;
        }

        // If orderColumn is the same, sort by createdAt.
        const aDate = new Date(a.createdAt as string).valueOf();
        const bDate = new Date(b.createdAt as string).valueOf();

        return (bDate - aDate) * multiplier;
      });
    }

    case 'title':
    case '-title': {
      const multiplier = sortOrder === 'title' ? 1 : -1;

      return sortedAchievements.sort((a, b) => {
        const aTitle = (a.title as string).toLowerCase();
        const bTitle = (b.title as string).toLowerCase();

        // Use localeCompare for proper string comparison.
        return aTitle.localeCompare(bTitle) * multiplier;
      });
    }

    case 'type':
    case '-type': {
      const multiplier = sortOrder === 'type' ? 1 : -1;

      return sortedAchievements.sort((a, b) => {
        const aValue = getTypeValue(a.type);
        const bValue = getTypeValue(b.type);

        if (aValue !== bValue) {
          return (aValue - bValue) * multiplier;
        }

        const orderDiff = (a.orderColumn as number) - (b.orderColumn as number);
        if (orderDiff !== 0) {
          return orderDiff * multiplier;
        }

        // Finally sort by ID.
        return ((a.id as number) - (b.id as number)) * multiplier;
      });
    }

    case 'wonBy':
    case '-wonBy': {
      const multiplier = sortOrder === 'wonBy' ? -1 : 1;

      return sortedAchievements.sort((a, b) => {
        const unlocksDiff = (a.unlocksHardcore as number) - (b.unlocksHardcore as number);
        if (unlocksDiff !== 0) {
          return unlocksDiff * multiplier;
        }

        // If unlocksTotal is the same, sort by orderColumn.
        return ((a.orderColumn as number) - (b.orderColumn as number)) * multiplier;
      });
    }

    case 'active':
      return sortedAchievements.sort((a, b) => {
        // Sort by status priority (active -> expired -> upcoming -> evergreen).
        const aStatus = getEventAchievementTimeStatus(a, eventAchievements);
        const bStatus = getEventAchievementTimeStatus(b, eventAchievements);
        if (aStatus !== bStatus) {
          return aStatus - bStatus;
        }

        // If the order column is set, use it.
        if (a.orderColumn !== b.orderColumn) {
          return (a.orderColumn as number) - (b.orderColumn as number);
        }

        // Fallback to sorting by creation date.
        const aDate = new Date(a.createdAt as string).valueOf();
        const bDate = new Date(b.createdAt as string).valueOf();

        return aDate - bDate;
      });

    default:
      return sortedAchievements;
  }
}

function getTypeValue(type?: string | null): number {
  switch (type) {
    case 'progression':
      return 0;

    case 'win_condition':
      return 1;

    case 'missable':
      return 2;

    case null:
      return 3;

    default:
      return 4;
  }
}

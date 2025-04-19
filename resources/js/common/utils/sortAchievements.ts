import dayjs from 'dayjs';
import isSameOrBefore from 'dayjs/plugin/isSameOrBefore';
import utc from 'dayjs/plugin/utc';

import type { AchievementSortOrder } from '../models';
import { getEventAchievementTimeStatus } from './getEventAchievementTimeStatus';

dayjs.extend(utc);
dayjs.extend(isSameOrBefore);

export function sortAchievements(
  achievements: App.Platform.Data.Achievement[],
  sortOrder: AchievementSortOrder,
  eventAchievements?: App.Platform.Data.EventAchievement[],
): App.Platform.Data.Achievement[] {
  const getIsAchievementUnlocked = (achievement: App.Platform.Data.Achievement): boolean => {
    return !!achievement.unlockedAt || !!achievement.unlockedHardcoreAt;
  };

  const compareUnlockStatus = (
    a: App.Platform.Data.Achievement,
    b: App.Platform.Data.Achievement,
  ): number => {
    const aUnlocked = getIsAchievementUnlocked(a);
    const bUnlocked = getIsAchievementUnlocked(b);

    // Always put unlocked achievements first, regardless of sort direction.
    if (aUnlocked !== bUnlocked) {
      return aUnlocked ? -1 : 1;
    }

    // If both are unlocked or both are not unlocked, return 0 to continue with the next comparison.
    return 0;
  };

  switch (sortOrder) {
    case 'normal':
    case '-normal': {
      const multiplier = sortOrder === 'normal' ? 1 : -1;

      return achievements.sort((a, b) => {
        // For 'normal' sort, the unlocked status is affected by direction.
        const unlockedResult = compareUnlockStatus(a, b);
        if (unlockedResult !== 0) {
          return unlockedResult * multiplier;
        }

        // Then sort by orderColumn within each group (unlocked and not unlocked).
        const orderDiff = (a.orderColumn as number) - (b.orderColumn as number);
        if (orderDiff !== 0) {
          return orderDiff * multiplier;
        }

        // If orderColumn is the same, sort by createdAt.
        const aDate = new Date(a.createdAt as string).valueOf();
        const bDate = new Date(b.createdAt as string).valueOf();

        return (aDate - bDate) * multiplier;
      });
    }

    case 'displayOrder':
    case '-displayOrder': {
      const multiplier = sortOrder === 'displayOrder' ? 1 : -1;

      return achievements.sort((a, b) => {
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

      return achievements.sort((a, b) => {
        // First, prioritize unlocked achievements.
        const unlockedResult = compareUnlockStatus(a, b);
        if (unlockedResult !== 0) {
          return unlockedResult;
        }

        // Then, sort by points value within each group (unlocked and not unlocked).
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

      return achievements.sort((a, b) => {
        // First, prioritize unlocked achievements.
        const unlockedResult = compareUnlockStatus(a, b);
        if (unlockedResult !== 0) {
          return unlockedResult;
        }

        // Then sort case-insensitively by title within each group (unlocked and not unlocked).
        const aTitle = (a.title as string).toLowerCase();
        const bTitle = (b.title as string).toLowerCase();

        // Use localeCompare for proper string comparison.
        return aTitle.localeCompare(bTitle) * multiplier;
      });
    }

    case 'type':
    case '-type': {
      const multiplier = sortOrder === 'type' ? 1 : -1;

      return achievements.sort((a, b) => {
        // First, prioritize unlocked achievements.
        const unlockedResult = compareUnlockStatus(a, b);
        if (unlockedResult !== 0) {
          return unlockedResult;
        }

        const getTypeValue = (type?: string | null): number => {
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
        };

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

      return achievements.sort((a, b) => {
        // First, prioritize unlocked achievements.
        const unlockedResult = compareUnlockStatus(a, b);
        if (unlockedResult !== 0) {
          return unlockedResult;
        }

        // Then, sort by unlocksHardcoreTotal within each group (unlocked and not unlocked).
        const unlocksDiff = (a.unlocksHardcoreTotal as number) - (b.unlocksHardcoreTotal as number);
        if (unlocksDiff !== 0) {
          return unlocksDiff * multiplier;
        }

        // If unlocksTotal is the same, sort by orderColumn.
        return ((a.orderColumn as number) - (b.orderColumn as number)) * multiplier;
      });
    }

    case 'active':
      return achievements.sort((a, b) => {
        // Sort by status priority (active -> expired -> upcoming -> evergreen).
        const aStatus = getEventAchievementTimeStatus(a, eventAchievements);
        const bStatus = getEventAchievementTimeStatus(b, eventAchievements);
        if (aStatus !== bStatus) {
          return aStatus - bStatus;
        }

        // If status is the same, sort by date.
        const aDate = new Date(a.createdAt as string).valueOf();
        const bDate = new Date(b.createdAt as string).valueOf();
        if (aDate !== bDate) {
          return aDate - bDate;
        }

        // If date is the same, sort by orderColumn.
        return (a.orderColumn as number) - (b.orderColumn as number);
      });

    default:
      return achievements;
  }
}

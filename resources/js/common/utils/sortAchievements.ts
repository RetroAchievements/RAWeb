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
  switch (sortOrder) {
    case 'displayOrder':
    case '-displayOrder': {
      const multiplier = sortOrder === 'displayOrder' ? 1 : -1;

      return achievements.sort((a, b) => {
        // First, sort by orderColumn if it exists.
        const orderDiff = (a.orderColumn ?? 0) - (b.orderColumn ?? 0);
        if (orderDiff !== 0) {
          return orderDiff * multiplier;
        }

        // Then, sort by createdAt if orderColumn is the same.
        const aDate = new Date(a.createdAt ?? 0).valueOf();
        const bDate = new Date(b.createdAt ?? 0).valueOf();

        return (aDate - bDate) * multiplier;
      });
    }

    case 'wonBy':
    case '-wonBy': {
      const multiplier = sortOrder === 'wonBy' ? -1 : 1;

      return achievements.sort((a, b) => {
        // First, sort by unlocksHardcoreTotal.
        const unlocksDiff = (a.unlocksHardcoreTotal ?? 0) - (b.unlocksHardcoreTotal ?? 0);
        if (unlocksDiff !== 0) {
          return unlocksDiff * multiplier;
        }

        // Then, sort by orderColumn if unlocksHardcoreTotal is the same.
        return ((a.orderColumn ?? 0) - (b.orderColumn ?? 0)) * multiplier;
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
        const aDate = new Date(a.createdAt ?? 0).valueOf();
        const bDate = new Date(b.createdAt ?? 0).valueOf();
        if (aDate !== bDate) {
          return aDate - bDate;
        }

        // If date is the same, sort by orderColumn.
        return (a.orderColumn ?? 0) - (b.orderColumn ?? 0);
      });

    default:
      return achievements;
  }
}

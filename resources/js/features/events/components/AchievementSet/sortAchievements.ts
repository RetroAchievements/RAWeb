import dayjs from 'dayjs';
import isSameOrBefore from 'dayjs/plugin/isSameOrBefore';
import utc from 'dayjs/plugin/utc';

import type { AchievementSortOrder } from '../../models';

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
        const aStatus = getStatus(a, eventAchievements);
        const bStatus = getStatus(b, eventAchievements);
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

/**
 * Returns the status priority for an event achievement:
 * 0 = Active
 * 1 = Upcoming (within next 30 days)
 * 2 = Expired
 * 3 = Future (more than 30 days away)
 * 4 = Evergreen
 */
export function getStatus(
  achievement: App.Platform.Data.Achievement,
  eventAchievements?: App.Platform.Data.EventAchievement[],
): 0 | 1 | 2 | 3 | 4 {
  const eventAchievement = eventAchievements?.find((ea) => ea.achievement?.id === achievement.id);
  if (!eventAchievement?.activeFrom || !eventAchievement?.activeUntil) return 4; // evergreen?

  const now = dayjs.utc();
  const activeFrom = dayjs.utc(eventAchievement.activeFrom);
  const activeUntil = dayjs.utc(eventAchievement.activeUntil);

  if (activeFrom.isSameOrBefore(now) && now.isSameOrBefore(activeUntil)) return 0; // Active.

  // Check if upcoming is within the next 30 days.
  const thirtyDaysFromNow = now.add(30, 'day');
  if (activeFrom.isAfter(now) && activeFrom.isSameOrBefore(thirtyDaysFromNow)) return 1; // Upcoming within 30 days.

  if (activeUntil.isBefore(now)) return 2; // Expired.

  return 3; // Future (more than 30 days away).
}

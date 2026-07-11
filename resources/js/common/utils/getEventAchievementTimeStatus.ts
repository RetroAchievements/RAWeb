import dayjs from 'dayjs';
import isSameOrBefore from 'dayjs/plugin/isSameOrBefore';
import utc from 'dayjs/plugin/utc';

import type { EventAchievementTimeStatus } from '../models';
import { eventAchievementTimeStatus } from './eventAchievementTimeStatus';

dayjs.extend(utc);
dayjs.extend(isSameOrBefore);

// TODO put this in the EventAchievementData DTO instead

/**
 * Returns the status priority for an event achievement:
 * 0 = Active
 * 1 = Expired
 * 2 = Upcoming (within next 30 days)
 * 3 = Future (more than 30 days away)
 * 4 = Evergreen
 */
export function getEventAchievementTimeStatus(
  achievement: App.Platform.Data.Achievement,
  eventAchievements?: App.Platform.Data.EventAchievement[],
): EventAchievementTimeStatus {
  const eventAchievement = eventAchievements?.find((ea) => ea.achievement?.id === achievement.id);
  if (!eventAchievement) {
    // Not found. Assume it's not restricted.
    return eventAchievementTimeStatus.evergreen;
  }

  const now = dayjs.utc();
  const activeFrom = eventAchievement.activeFrom ?? eventAchievement.event?.activeFrom;
  if (activeFrom) {
    const activeFromDate = dayjs.utc(activeFrom);

    if (activeFromDate.isAfter(now)) {
      // Not active yet.
      const thirtyDaysFromNow = now.add(30, 'day');
      if (activeFromDate.isSameOrBefore(thirtyDaysFromNow)) {
        // 30 days or less until before it becomes active.
        return eventAchievementTimeStatus.upcoming;
      }

      // More than 30 days before it becomes active.
      return eventAchievementTimeStatus.future;
    }
  }

  const activeUntil = eventAchievement.activeUntil ?? eventAchievement.event?.activeUntil;
  if (!activeUntil) {
    // Active with no end date.
    return eventAchievementTimeStatus.evergreen;
  }

  const activeUntilDate = dayjs.utc(activeUntil);
  if (activeUntilDate.isBefore(now)) {
    // No longer active.
    return eventAchievementTimeStatus.expired;
  }

  // Active.
  return eventAchievementTimeStatus.active;
}

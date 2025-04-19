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
  if (!eventAchievement?.activeFrom || !eventAchievement?.activeThrough) {
    return eventAchievementTimeStatus.evergreen;
  }

  const now = dayjs.utc();
  const activeFrom = dayjs.utc(eventAchievement.activeFrom);
  const activeThrough = dayjs.utc(eventAchievement.activeThrough);
  const activeUntil = dayjs.utc(eventAchievement.activeUntil);

  if (activeFrom.isSameOrBefore(now) && now.isBefore(activeUntil)) {
    return eventAchievementTimeStatus.active;
  }

  // Check if upcoming is within the next 30 days.
  const thirtyDaysFromNow = now.add(30, 'day');
  if (activeFrom.isAfter(now) && activeFrom.isSameOrBefore(thirtyDaysFromNow)) {
    return eventAchievementTimeStatus.upcoming;
  }

  if (activeThrough.isBefore(now)) {
    return eventAchievementTimeStatus.expired;
  }

  // More than 30 days away.
  return eventAchievementTimeStatus.future;
}

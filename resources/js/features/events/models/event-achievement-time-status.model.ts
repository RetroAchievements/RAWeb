import type { eventAchievementTimeStatus } from '../utils/eventAchievementTimeStatus';

export type EventAchievementTimeStatus =
  (typeof eventAchievementTimeStatus)[keyof typeof eventAchievementTimeStatus];

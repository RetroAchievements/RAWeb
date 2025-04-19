export const eventAchievementTimeStatus = {
  active: 0,

  expired: 1,

  /** The event achievement start date is within the next 30 days. */
  upcoming: 2,

  /** The event achievement start date is more than 30 days away. */
  future: 3,

  evergreen: 4,
} as const;

import { createFactory } from '@/test/createFactory';

import { createEventAchievement } from '../createEventAchievement';

export const createAchievementOfTheWeekProps =
  createFactory<App.Http.Data.AchievementOfTheWeekProps>((faker) => {
    return {
      achievementOfTheWeekProgress: {
        streakLength: faker.number.int({ min: 0, max: 52 }),
        hasActiveStreak: faker.datatype.boolean(),
        hasCurrentWeek: faker.datatype.boolean(),
      },
      currentEventAchievement: createEventAchievement(),
    };
  });

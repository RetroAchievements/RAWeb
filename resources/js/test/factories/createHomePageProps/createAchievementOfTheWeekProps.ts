import { createFactory } from '@/test/createFactory';

import { createEventAchievement } from '../createEventAchievement';

export const createAchievementOfTheWeekProps =
  createFactory<App.Http.Data.AchievementOfTheWeekProps>((faker) => {
    return {
      currentEventAchievement: createEventAchievement(),
      doesUserHaveUnlock: faker.datatype.boolean(),
    };
  });

import { createFactory } from '../createFactory';
import { createAchievement } from './createAchievement';

export const createAchievementGroup = createFactory<App.Community.Data.AchievementGroup>(
  (faker) => {
    return {
      header: faker.word.words(2),
      achievements: [createAchievement(), createAchievement()],
    };
  },
);

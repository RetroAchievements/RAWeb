import { createFactory } from '../createFactory';
import { createAchievement } from './createAchievement';

export const createAchievementChecklistGroup =
  createFactory<App.Community.Data.AchievementChecklistGroup>((faker) => {
    return {
      header: faker.word.words(2),
      achievements: [createAchievement(), createAchievement()],
    };
  });

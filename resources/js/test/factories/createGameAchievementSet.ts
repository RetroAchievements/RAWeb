import { createFactory } from '../createFactory';
import { createAchievementSet } from './createAchievementSet';

export const createGameAchievementSet = createFactory<App.Platform.Data.GameAchievementSet>(
  (faker) => {
    return {
      achievementSet: createAchievementSet(),
      createdAt: faker.date.recent().toISOString(),
      id: faker.number.int({ min: 1, max: 999_999 }),
      orderColumn: 0,
      title: faker.word.words(4),
      type: 'core',
      updatedAt: faker.date.recent().toISOString(),
    };
  },
);

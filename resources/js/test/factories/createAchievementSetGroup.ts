import { createFactory } from '../createFactory';

export const createAchievementSetGroup = createFactory<App.Platform.Data.AchievementSetGroup>(
  (faker) => {
    return {
      achievementCount: faker.number.int({ min: 1, max: 100 }),
      badgeUrl: faker.internet.url(),
      id: faker.number.int({ min: 1, max: 999999 }),
      label: faker.word.words(2),
      orderColumn: faker.number.int({ min: 0, max: 100 }),
    };
  },
);

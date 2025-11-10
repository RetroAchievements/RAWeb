import { createFactory } from '../createFactory';

export const createPlayerAchievementSet = createFactory<App.Platform.Data.PlayerAchievementSet>(
  (faker) => {
    return {
      completedAt: faker.helpers.arrayElement([null, faker.date.recent().toISOString()]),
      completedHardcoreAt: faker.helpers.arrayElement([null, faker.date.recent().toISOString()]),
      timeTaken: faker.helpers.arrayElement([null, faker.number.int({ min: 1000, max: 100000 })]),
      timeTakenHardcore: faker.helpers.arrayElement([
        null,
        faker.number.int({ min: 1000, max: 100000 }),
      ]),
    };
  },
);

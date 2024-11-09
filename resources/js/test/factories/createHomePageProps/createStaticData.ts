import { createFactory } from '@/test/createFactory';

export const createStaticData = createFactory<App.Data.StaticData>((faker) => {
  return {
    numAchievements: faker.number.int({ min: 0, max: 100_000 }),
    numAwarded: faker.number.int({ min: 0, max: 100_000 }),
    numGames: faker.number.int({ min: 0, max: 20_000 }),
    numHardcoreGameBeatenAwards: faker.number.int({ min: 0, max: 900_000 }),
    numHardcoreMasteryAwards: faker.number.int({ min: 0, max: 900_000 }),
    numRegisteredUsers: faker.number.int({ min: 0, max: 10_000_000 }),
    totalPointsEarned: faker.number.int({ min: 0, max: 500_000_000 }),
    eventAotwForumId: faker.number.int({ min: 1, max: 50000 }),
  };
});

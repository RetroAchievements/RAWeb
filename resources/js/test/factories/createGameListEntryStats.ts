import { createFactory } from '../createFactory';

export const createGameListEntryStats = createFactory<App.Platform.Data.GameListEntryStats>(
  (faker) => {
    return {
      coreSetMedianTimeToCompleteHardcore: faker.number.int({ min: 0, max: 10_000 }),
      coreSetPlayersHardcore: faker.number.int({ min: 0, max: 1_000 }),
      coreSetTimesCompletedHardcore: faker.number.int({ min: 0, max: 1_000 }),
    };
  },
);

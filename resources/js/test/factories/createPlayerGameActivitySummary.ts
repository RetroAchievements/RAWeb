import { createFactory } from '../createFactory';

export const createPlayerGameActivitySummary =
  createFactory<App.Platform.Data.PlayerGameActivitySummary>((faker) => {
    return {
      achievementPlaytime: faker.number.int({ min: 60, max: 3600 }),
      achievementSessionCount: faker.number.int({ min: 1, max: 100 }),
      generatedSessionAdjustment: 0,
      totalPlaytime: faker.number.int({ min: 60, max: 9000 }),
      totalUnlockTime: faker.number.int({ min: 60, max: 9000 }),
    };
  });

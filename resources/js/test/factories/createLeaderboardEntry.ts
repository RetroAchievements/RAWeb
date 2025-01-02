import { createFactory } from '../createFactory';

export const createLeaderboardEntry = createFactory<App.Platform.Data.LeaderboardEntry>((faker) => {
  return {
    id: faker.number.int({ min: 1, max: 999_999 }),
    score: faker.number.int({ min: 0, max: 999_999 }),
    formattedScore: '123,456',
    createdAt: faker.date.recent().toISOString(),
  };
});

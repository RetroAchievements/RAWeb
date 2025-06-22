import { createFactory } from '../createFactory';
import { createGameSet } from './createGameSet';

export const createSeriesHub = createFactory<App.Platform.Data.SeriesHub>((faker) => {
  return {
    achievementsPublished: faker.number.int({ min: 6, max: 100 }),
    hub: createGameSet(),
    pointsTotal: faker.number.int({ min: 400, max: 1000 }),
    totalGameCount: 20,
    gamesWithAchievementsCount: 15,
  };
});

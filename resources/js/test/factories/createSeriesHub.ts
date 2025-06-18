import { createFactory } from '../createFactory';
import { createGame } from './createGame';
import { createGameSet } from './createGameSet';

export const createSeriesHub = createFactory<App.Platform.Data.SeriesHub>((faker) => {
  return {
    achievementsPublished: faker.number.int({ min: 6, max: 100 }),
    additionalGameCount: 10,
    hub: createGameSet(),
    pointsTotal: faker.number.int({ min: 400, max: 1000 }),
    topGames: [createGame(), createGame(), createGame(), createGame(), createGame()],
    totalGameCount: 20,
  };
});

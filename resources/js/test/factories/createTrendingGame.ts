import { createFactory } from '@/test/createFactory';

import { createGame } from './createGame';

export const createTrendingGame = createFactory<App.Community.Data.TrendingGame>((faker) => {
  return {
    game: createGame(),
    playerCount: faker.number.int({ min: 10, max: 500 }),
  };
});

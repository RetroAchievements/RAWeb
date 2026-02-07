import { createFactory } from '@/test/createFactory';

import { createGame } from './createGame';

export const createGameActivitySnapshot = createFactory<App.Community.Data.GameActivitySnapshot>(
  (faker) => {
    return {
      game: createGame(),
      playerCount: faker.number.int({ min: 10, max: 500 }),
      trendingReason: faker.helpers.arrayElement([
        'new-set',
        'revised-set',
        'gaining-traction',
        'renewed-interest',
        'many-more-players',
        'more-players',
        null,
      ]),
      event: null,
    };
  },
);

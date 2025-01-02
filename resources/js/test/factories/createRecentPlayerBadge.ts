import { createFactory } from '../createFactory';
import { createGame } from './createGame';
import { createUser } from './createUser';

export const createRecentPlayerBadge = createFactory<App.Community.Data.RecentPlayerBadge>(
  (faker) => {
    return {
      game: createGame(),
      awardType: faker.helpers.arrayElement([
        'beaten-softcore',
        'beaten-hardcore',
        'completed',
        'mastered',
      ]),
      earnedAt: faker.date.recent().toISOString(),
      user: createUser(),
    };
  },
);

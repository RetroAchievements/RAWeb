import { createFactory } from '../createFactory';
import { createUser } from './createUser';

export const createRankedGameTopAchiever = createFactory<App.Platform.Data.RankedGameTopAchiever>(
  (faker) => {
    return {
      rank: 1,
      user: createUser(),
      score: faker.number.int({ min: 1, max: 100 }),
      badge: null,
    };
  },
);

import { createFactory } from '@/test/createFactory';

import { createGame } from '../createGame';
import { createUser } from '../createUser';

export const createStaticGameAward = createFactory<App.Data.StaticGameAward>((faker) => {
  return {
    awardedAt: faker.date.recent().toISOString(),
    game: createGame(),
    user: createUser(),
  };
});

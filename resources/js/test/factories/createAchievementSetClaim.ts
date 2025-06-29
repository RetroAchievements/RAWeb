import { createFactory } from '../createFactory';
import { createGame } from './createGame';
import { createUser } from './createUser';

export const createAchievementSetClaim = createFactory<App.Platform.Data.AchievementSetClaim>(
  (faker) => {
    return {
      id: faker.number.int({ min: 1, max: 99999 }),
      game: createGame(),
      user: createUser(),
      finishedAt: faker.date.soon().toISOString(),
    };
  },
);

import { createFactory } from '../createFactory';
import { createUser } from './createUser';

export const createGameTopAchiever = createFactory<App.Platform.Data.GameTopAchiever>((faker) => {
  return {
    rank: 1,
    user: createUser(),
    score: faker.number.int({ min: 1, max: 100 }),
    badge: null,
  };
});

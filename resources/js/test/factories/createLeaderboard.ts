import { createFactory } from '../createFactory';
import { createGame } from './createGame';

export const createLeaderboard = createFactory<App.Platform.Data.Leaderboard>((faker) => {
  return {
    id: faker.number.int({ min: 1, max: 99999 }),
    title: faker.word.words(3),
    description: faker.word.words(12),
    game: createGame(),
  };
});

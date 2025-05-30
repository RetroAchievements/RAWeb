import { createFactory } from '../createFactory';
import { createGame } from './createGame';

export const createEvent = createFactory<App.Platform.Data.Event>((faker) => {
  return {
    activeFrom: null,
    activeThrough: null,
    id: faker.number.int({ min: 1, max: 10000 }),
    legacyGame: createGame(),
  };
});

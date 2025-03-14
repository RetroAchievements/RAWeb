import { createFactory } from '../createFactory';
import { createGame } from './createGame';

/**
 * This is not named `createEvent` because @testing-library/react already has
 * an export named `createEvent`. If these two exports from our @/test module
 * share the same name, IDEs get very confused.
 */
export const createRaEvent = createFactory<App.Platform.Data.Event>((faker) => {
  return {
    id: faker.number.int({ min: 1, max: 10000 }),
    legacyGame: createGame(),
    activeFrom: faker.date.past().toISOString(),
    activeThrough: faker.date.future().toISOString(),
  };
});

import { createFactory } from '../createFactory';
import { createGame } from './createGame';

/**
 * This is not named `createEvent` because @testing-library/react already has
 * an export named `createEvent`. If these two exports from our @/test module
 * share the same name, IDEs get very confused.
 */
export const createRaEvent = createFactory<App.Platform.Data.Event>((faker) => {
  const activeThrough = faker.date.future();
  return {
    id: faker.number.int({ min: 1, max: 10000 }),
    legacyGame: createGame(),
    activeFrom: faker.date.past().toISOString(),
    activeThrough: activeThrough.toISOString(),
    activeUntil: new Date(activeThrough.getTime() + 24 * 60 * 60 * 1000).toISOString(),
    state: faker.helpers.arrayElement(['active', 'evergreen', 'concluded']),
  };
});

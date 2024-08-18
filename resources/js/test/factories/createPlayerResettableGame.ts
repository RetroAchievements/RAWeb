import { createFactory } from '../createFactory';

export const createPlayerResettableGame = createFactory<App.Platform.Data.PlayerResettableGame>(
  (faker) => ({
    id: faker.number.int({ min: 1, max: 99999 }),
    title: faker.word.words(2),
    consoleName: faker.word.words(2),
    numAwarded: faker.number.int({ min: 1, max: 50 }),
    numPossible: faker.number.int({ min: 1, max: 50 }),
  }),
);

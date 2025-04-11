import { createFactory } from '../createFactory';

export const createEmulator = createFactory<App.Platform.Data.Emulator>((faker) => {
  return {
    id: faker.number.int({ min: 1, max: 200 }),
    name: faker.word.words(2),
    canDebugTriggers: null,
  };
});

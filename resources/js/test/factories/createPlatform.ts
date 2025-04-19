import { createFactory } from '../createFactory';

export const createPlatform = createFactory<App.Platform.Data.Platform>((faker) => {
  return {
    executionEnvironment: 'desktop',
    id: faker.number.int({ min: 1, max: 999_999 }),
    name: faker.word.words(1),
    orderColumn: 0,
  };
});

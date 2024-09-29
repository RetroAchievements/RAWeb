import { createFactory } from '../createFactory';

export const createSystem = createFactory<App.Platform.Data.System>((faker) => {
  return {
    id: faker.number.int({ min: 1, max: 150 }),
    name: faker.word.words(2),
    nameFull: faker.word.words(4),
    nameShort: faker.string.alphanumeric(3),
    iconUrl: faker.internet.url(),
  };
});

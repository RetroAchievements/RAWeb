import { createFactory } from '../createFactory';
import { createSystem } from './createSystem';

export const createGame = createFactory<App.Platform.Data.Game>((faker) => {
  return {
    id: faker.number.int({ min: 1, max: 99999 }),
    title: faker.word.words(3),
    badgeUrl: faker.internet.url(),
    forumTopicId: faker.number.int({ min: 1, max: 99999 }),
    imageIngameDimensions: { width: 256, height: 224 },
    imageTitleDimensions: { width: 256, height: 224 },
    system: createSystem(),
  };
});

import { createFactory } from '../createFactory';
import { createUser } from './createUser';

export const createNews = createFactory<App.Data.News>((faker) => {
  return {
    body: faker.word.words(24),
    createdAt: faker.date.recent().toISOString(),
    id: faker.number.int({ min: 1, max: 10000 }),
    imageAssetPath: faker.internet.url(),
    lead: faker.word.words(24),
    link: faker.internet.url(),
    pinnedAt: null,
    publishAt: null,
    title: faker.word.words(3),
    unpublishAt: null,
    user: createUser(),
  };
});

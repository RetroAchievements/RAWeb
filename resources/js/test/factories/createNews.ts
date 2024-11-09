import { createFactory } from '../createFactory';
import { createUser } from './createUser';

export const createNews = createFactory<App.Data.News>((faker) => {
  return {
    id: faker.number.int({ min: 1, max: 10000 }),
    image: faker.internet.url(),
    lead: faker.word.words(24),
    link: faker.internet.url(),
    payload: faker.word.words(24),
    timestamp: faker.date.recent().toISOString(),
    title: faker.word.words(3),
    user: createUser(),
  };
});

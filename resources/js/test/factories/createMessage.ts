import { createFactory } from '../createFactory';
import { createUser } from './createUser';

export const createMessage = createFactory<App.Community.Data.Message>((faker) => {
  return {
    id: faker.number.int({ min: 1, max: 99999 }),
    body: faker.word.words(30),
    createdAt: faker.date.recent().toISOString(),
    author: createUser(),
  };
});

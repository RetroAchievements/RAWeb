import { createFactory } from '../createFactory';
import { createUser } from './createUser';

export const createComment = createFactory<App.Community.Data.Comment>((faker) => {
  return {
    canDelete: false,
    commentableId: faker.number.int({ min: 1, max: 10000 }),
    commentableType: 1,
    createdAt: faker.date.recent().toISOString(),
    id: faker.number.int({ min: 1, max: 999999 }),
    payload: faker.word.words(20),
    updatedAt: faker.date.recent().toISOString(),
    user: createUser(),
    isAutomated: false,
  };
});

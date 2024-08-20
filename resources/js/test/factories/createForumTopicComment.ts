import { createFactory } from '../createFactory';
import { createUser } from './createUser';

export const createForumTopicComment = createFactory<App.Data.ForumTopicComment>((faker) => {
  return {
    authorized: faker.datatype.boolean(),
    body: faker.word.words(20),
    createdAt: faker.date.recent().toISOString(),
    forumTopicId: faker.number.int({ min: 1, max: 999999 }),
    id: faker.number.int({ min: 1, max: 999999 }),
    updatedAt: faker.date.recent().toISOString(),
    user: createUser(),
  };
});

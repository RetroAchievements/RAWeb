import { createFactory } from '../createFactory';

export const createForumTopic = createFactory<App.Data.ForumTopic>((faker) => {
  return {
    id: faker.number.int({ min: 1, max: 9999 }),
    title: faker.word.words(3),
    createdAt: faker.date.recent().toISOString(),
    updatedAt: null,
    user: null,
  };
});

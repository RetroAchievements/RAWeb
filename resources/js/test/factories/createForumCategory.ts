import { createFactory } from '../createFactory';

export const createForumCategory = createFactory<App.Data.ForumCategory>((faker) => {
  return {
    id: faker.number.int({ min: 1, max: 10000 }),
    title: faker.word.words(3),
  };
});

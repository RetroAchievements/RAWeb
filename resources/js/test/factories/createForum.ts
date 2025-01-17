import { createFactory } from '../createFactory';

export const createForum = createFactory<App.Data.Forum>((faker) => {
  return {
    id: faker.number.int({ min: 1, max: 1000 }),
    title: faker.word.words(5),
  };
});

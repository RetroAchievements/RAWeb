import { createFactory } from '../createFactory';
import { createUser } from './createUser';

export const createComment = createFactory<App.Community.Data.Comment>((faker) => {
  return {
    canDelete: false,
    canReport: false,
    commentableId: faker.number.int({ min: 1, max: 10000 }),
    commentableType: 'game.comment',
    createdAt: faker.date.recent().toISOString(),
    id: faker.number.int({ min: 1, max: 999999 }),
    payload: faker.word.words(20),
    updatedAt: faker.date.recent().toISOString(),
    user: createUser(),
    isAutomated: false,
    url: null,
  };
});

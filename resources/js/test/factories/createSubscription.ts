import { createFactory } from '../createFactory';

export const createSubscription = createFactory<App.Community.Data.Subscription>((faker) => {
  return {
    id: faker.number.int({ min: 1, max: 100000 }),
    state: faker.datatype.boolean(),
    subjectId: faker.number.int({ min: 1, max: 100000 }),
    subjectType: faker.helpers.arrayElement([
      'ForumTopic',
      'UserWall',
      'GameWall',
      'Achievement',
      'GameTickets',
      'GameAchievements',
    ]),
  };
});

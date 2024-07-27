import { createFactory } from '../createFactory';

export const createUser = createFactory<App.Data.User>((faker) => {
  const displayName = faker.internet.displayName();

  return {
    displayName,
    avatarUrl: `http://media.retroachievements.org/UserPic/${displayName}.png`,
    id: faker.number.int({ min: 1, max: 1000000 }),
    legacyPermissions: faker.number.int({ min: 0, max: 4 }),
    preferences: {
      prefersAbsoluteDates: faker.datatype.boolean(),
    },
    roles: [],
    unreadMessageCount: faker.number.int({ min: 0, max: 3 }),
  };
});

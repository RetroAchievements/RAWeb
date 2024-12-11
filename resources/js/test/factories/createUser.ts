import { createFactory } from '../createFactory';

export const createUser = createFactory<App.Data.User>((faker) => {
  const displayName = faker.internet.displayName();

  return {
    displayName,
    username: displayName,
    isMuted: faker.datatype.boolean(),
    mutedUntil: null,
    avatarUrl: `http://media.retroachievements.org/UserPic/${displayName}.png`,
    id: faker.number.int({ min: 1, max: 1000000 }),
    legacyPermissions: faker.number.int({ min: 0, max: 4 }),
    preferences: {
      prefersAbsoluteDates: faker.datatype.boolean(),
    },
    playerPreferredMode: 'hardcore',
    roles: [],
    unreadMessageCount: faker.number.int({ min: 0, max: 3 }),
  };
});

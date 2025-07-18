import { createFactory } from '../createFactory';

export const createUser = createFactory<App.Data.User>((faker) => {
  const displayName = faker.internet.displayName();

  return {
    displayName,
    createdAt: faker.date.recent().toISOString(),
    username: displayName,
    isMuted: faker.datatype.boolean(),
    mutedUntil: null,
    avatarUrl: `${displayName}.png`,
    id: faker.number.int({ min: 1, max: 1000000 }),
    legacyPermissions: faker.number.int({ min: 0, max: 4 }),
    preferences: {
      prefersAbsoluteDates: faker.datatype.boolean(),
      shouldAlwaysBypassContentWarnings: faker.datatype.boolean(),
    },
    playerPreferredMode: 'hardcore',
    roles: [],
    unreadMessageCount: faker.number.int({ min: 0, max: 3 }),
  };
});

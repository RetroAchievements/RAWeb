import { createFactory } from '@/test/createFactory';

interface UserPreferences {
  prefersAbsoluteDates: boolean;
}

export interface User {
  avatarUrl: string;
  displayName: string;
  id: number;
  legacyPermissions: number;
  preferences: UserPreferences;
  unreadMessageCount: number;
}

export const createUser = createFactory<User>((faker) => {
  const displayName = faker.internet.displayName();

  return {
    displayName,
    avatarUrl: `http://media.retroachievements.org/UserPic/${displayName}.png`,
    id: faker.number.int({ min: 1, max: 1000000 }),
    legacyPermissions: faker.number.int({ min: 0, max: 4 }),
    preferences: {
      prefersAbsoluteDates: faker.datatype.boolean(),
    },
    unreadMessageCount: faker.number.int({ min: 0, max: 3 }),
  };
});

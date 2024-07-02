import { faker } from '@faker-js/faker';

import type { User } from '@/common/models';

export function createUser(props?: Partial<User>): User {
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

    ...props,
  };
}

import { createFactory } from '../createFactory';
import { createGame } from './createGame';
import { createUser } from './createUser';

export const createAchievementSetClaimGroup = createFactory<App.Data.AchievementSetClaimGroup>(
  (faker) => {
    return {
      claimType: faker.helpers.arrayElement<App.Community.Enums.ClaimType>([
        'primary',
        'collaboration',
      ]),
      created: faker.date.past().toISOString(),
      finished: faker.date.recent().toISOString(),
      game: createGame(),
      id: faker.number.int({ min: 1, max: 50000 }),
      setType: faker.helpers.arrayElement<App.Community.Enums.ClaimSetType>([
        'new_set',
        'revision',
      ]),
      status: faker.helpers.arrayElement<App.Community.Enums.ClaimStatus>([
        'active',
        'complete',
        'dropped',
        'in_review',
      ]),
      users: [createUser()],
    };
  },
);

import { ClaimSetType, ClaimStatus, ClaimType } from '@/common/utils/generatedAppConstants';

import { createFactory } from '../createFactory';
import { createGame } from './createGame';
import { createUser } from './createUser';

export const createAchievementSetClaimGroup = createFactory<App.Data.AchievementSetClaimGroup>(
  (faker) => {
    return {
      claimType: faker.helpers.arrayElement([...Object.values(ClaimType)]),
      created: faker.date.past().toISOString(),
      finished: faker.date.recent().toISOString(),
      game: createGame(),
      id: faker.number.int({ min: 1, max: 50000 }),
      setType: faker.helpers.arrayElement([...Object.values(ClaimSetType)]),
      status: faker.helpers.arrayElement([...Object.values(ClaimStatus)]),
      users: [createUser()],
    };
  },
);

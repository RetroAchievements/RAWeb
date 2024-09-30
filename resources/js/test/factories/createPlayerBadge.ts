import { AwardType } from '@/common/utils/generatedAppConstants';

import { createFactory } from '../createFactory';

export const createPlayerBadge = createFactory<App.Platform.Data.PlayerBadge>((faker) => {
  return {
    awardType: faker.helpers.arrayElement([AwardType.Mastery, AwardType.GameBeaten]),
    awardData: faker.number.int({ min: 0, max: 20000 }),
    awardDataExtra: faker.helpers.arrayElement([0, 1]),
    awardDate: faker.date.recent().toISOString(),
  };
});

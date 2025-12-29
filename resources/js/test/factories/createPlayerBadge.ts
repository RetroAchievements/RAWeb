import { createFactory } from '../createFactory';

export const createPlayerBadge = createFactory<App.Platform.Data.PlayerBadge>((faker) => {
  return {
    awardType: faker.helpers.arrayElement<App.Community.Enums.AwardType>([
      'mastery',
      'game_beaten',
    ]),
    awardKey: faker.number.int({ min: 0, max: 20000 }),
    awardTier: faker.helpers.arrayElement([0, 1]),
    awardDate: faker.date.recent().toISOString(),
  };
});

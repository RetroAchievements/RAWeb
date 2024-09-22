import { createFactory } from '../createFactory';

// TODO use native enum values
const Mastery = 1;
const GameBeaten = 8;

export const createPlayerBadge = createFactory<App.Platform.Data.PlayerBadge>((faker) => {
  return {
    awardType: faker.helpers.arrayElement([Mastery, GameBeaten]),
    awardData: faker.number.int({ min: 0, max: 20000 }),
    awardDataExtra: faker.helpers.arrayElement([0, 1]),
    awardDate: faker.date.recent().toISOString(),
  };
});

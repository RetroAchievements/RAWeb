import { createFactory } from '../createFactory';
import { createPlayerBadge } from './createPlayerBadge';

export const createPlayerGame = createFactory<App.Platform.Data.PlayerGame>((faker) => {
  return {
    achievementsUnlocked: faker.number.int({ min: 0, max: 100 }),
    achievementsUnlockedHardcore: faker.number.int({ min: 0, max: 100 }),
    achievementsUnlockedSoftcore: faker.number.int({ min: 0, max: 100 }),
    beatenAt: faker.helpers.arrayElement([null, faker.date.recent().toISOString()]),
    beatenHardcoreAt: faker.helpers.arrayElement([null, faker.date.recent().toISOString()]),
    completedAt: faker.helpers.arrayElement([null, faker.date.recent().toISOString()]),
    completedHardcoreAt: faker.helpers.arrayElement([null, faker.date.recent().toISOString()]),
    points: faker.number.int({ min: 0, max: 1000 }),
    pointsHardcore: faker.number.int({ min: 0, max: 1000 }),
    highestAward: faker.helpers.arrayElement([null, null, createPlayerBadge()]), // 33% chance of having a badge
  };
});

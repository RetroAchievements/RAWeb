import { createFactory } from '../createFactory';
import { createGame } from './createGame';

export const createAchievement = createFactory<App.Platform.Data.Achievement>((faker) => {
  return {
    id: faker.number.int({ min: 1, max: 99999 }),
    description: faker.word.words(12),
    title: faker.word.words(3),
    badgeLockedUrl: faker.internet.url(),
    badgeUnlockedUrl: faker.internet.url(),
    game: createGame(),
    points: faker.helpers.arrayElement([0, 1, 2, 3, 4, 5, 10, 25, 50, 100]),
    pointsWeighted: faker.number.int({ min: 0, max: 1000 }),
  };
});

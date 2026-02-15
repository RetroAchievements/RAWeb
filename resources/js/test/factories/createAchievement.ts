import { createFactory } from '../createFactory';
import { createGame } from './createGame';
import { createUser } from './createUser';

export const createAchievement = createFactory<App.Platform.Data.Achievement>((faker) => {
  return {
    badgeLockedUrl: faker.internet.url(),
    badgeUnlockedUrl: faker.internet.url(),
    createdAt: faker.date.past().toISOString(),
    description: faker.word.words(12),
    developer: createUser(),
    game: createGame(),
    id: faker.number.int({ min: 1, max: 99999 }),
    modifiedAt: faker.date.recent().toISOString(),
    points: faker.helpers.arrayElement([0, 1, 2, 3, 4, 5, 10, 25, 50, 100]),
    pointsWeighted: faker.number.int({ min: 0, max: 1000 }),
    title: faker.word.words(3),
  };
});

import { createFactory } from '../createFactory';
import { createGame } from './createGame';

export const createAchievement = createFactory<App.Platform.Data.Achievement>((faker) => {
  return {
    id: faker.number.int({ min: 1, max: 99999 }),
    title: faker.word.words(3),
    badgeLockedUrl: faker.internet.url(),
    badgeUnlockedUrl: faker.internet.url(),
    game: createGame(),
  };
});

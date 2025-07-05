import { createFactory } from '../createFactory';
import { createUser } from './createUser';

export const createGameRecentPlayer = createFactory<App.Platform.Data.GameRecentPlayer>((faker) => {
  return {
    achievementsUnlocked: 0,
    achievementsUnlockedHardcore: 0,
    achievementsUnlockedSoftcore: 0,
    isActive: false,
    points: 0,
    pointsHardcore: 0,
    richPresence: faker.word.words(10),
    richPresenceUpdatedAt: faker.date.recent().toISOString(),
    user: createUser(),
  };
});

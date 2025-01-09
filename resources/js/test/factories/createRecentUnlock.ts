import { createFactory } from '../createFactory';
import { createAchievement } from './createAchievement';
import { createGame } from './createGame';
import { createSystem } from './createSystem';
import { createUser } from './createUser';

export const createRecentUnlock = createFactory<App.Community.Data.RecentUnlock>((faker) => {
  const system = createSystem();
  const game = createGame({ system });
  const achievement = createAchievement({ game });

  return {
    achievement,
    game,
    isHardcore: faker.datatype.boolean(),
    unlockedAt: faker.date.recent().toISOString(),
    user: createUser(),
  };
});

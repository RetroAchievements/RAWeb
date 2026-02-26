import { createFactory } from '../createFactory';
import { createUser } from './createUser';

export const createAchievementRecentUnlock =
  createFactory<App.Platform.Data.AchievementRecentUnlock>((faker) => ({
    isHardcore: faker.datatype.boolean(),
    unlockedAt: faker.date.recent().toISOString(),
    user: createUser(),
  }));

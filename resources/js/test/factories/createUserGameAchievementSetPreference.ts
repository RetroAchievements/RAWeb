import { createFactory } from '../createFactory';

export const createUserGameAchievementSetPreference =
  createFactory<App.Platform.Data.UserGameAchievementSetPreference>((faker) => {
    return {
      gameAchievementSetId: faker.number.int({ min: 1, max: 999_999 }),
      optedIn: true,
    };
  });

import { createFactory } from '../createFactory';
import { createUser } from './createUser';

export const createGameTopAchiever = createFactory<App.Platform.Data.GameTopAchiever>((faker) => {
  const user = createUser({ id: faker.number.int({ min: 1, max: 9999 }) });

  return {
    user,
    achievementsUnlockedHardcore: faker.number.int({ min: 1, max: 100 }),
    beatenHardcoreAt: null,
    lastUnlockHardcoreAt: faker.date.recent().toISOString(),
    pointsHardcore: faker.number.int({ min: 5, max: 300 }),
    userId: user.id as number,
  };
});

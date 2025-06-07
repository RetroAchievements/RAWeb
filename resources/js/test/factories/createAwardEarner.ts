import { createFactory } from '../createFactory';
import { createUser } from './createUser';

export const createAwardEarner = createFactory<App.Platform.Data.AwardEarner>((faker) => {
  const user = createUser({ id: faker.number.int({ min: 1, max: 9999 }) });

  return {
    user,
    dateEarned: faker.date.recent().toISOString(),
  };
});

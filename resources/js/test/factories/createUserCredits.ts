import { createFactory } from '../createFactory';

export const createUserCredits = createFactory<App.Platform.Data.UserCredits>((faker) => {
  return {
    avatarUrl: faker.internet.url(),
    count: faker.number.int({ min: 1, max: 100 }),
    dateCredited: faker.date.recent().toISOString(),
    displayName: faker.internet.displayName(),
  };
});

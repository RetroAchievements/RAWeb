import { createFactory } from '../createFactory';

export const createConnectedOAuthApplication = createFactory<App.Data.ConnectedOAuthApplication>(
  (faker) => {
    return {
      clientId: faker.string.uuid(),
      name: faker.word.words(2),
      scopes: ['data:read'],
      connectedAt: faker.date.recent().toISOString(),
    };
  },
);

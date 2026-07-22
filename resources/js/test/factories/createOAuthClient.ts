import { createFactory } from '../createFactory';

export const createOAuthClient = createFactory<App.Data.OAuthClient>((faker) => {
  return {
    id: faker.string.uuid(),
    name: faker.word.words(2),
    redirectUris: [faker.internet.url()],
    grantTypes: ['authorization_code', 'refresh_token'],
    revoked: false,
    createdAt: faker.date.recent().toISOString(),
    updatedAt: faker.date.recent().toISOString(),
    confidential: true,
  };
});

import { createFactory } from '../createFactory';

export const createParsedUserAgent = createFactory<App.Platform.Data.ParsedUserAgent>(() => {
  return {
    client: 'RALibRetro',
    clientVersion: '1.19.1',
    clientVariation: null,
    extra: null,
    integrationVersion: null,
    os: null,
  };
});

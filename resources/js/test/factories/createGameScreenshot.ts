import { createFactory } from '../createFactory';

export const createGameScreenshot = createFactory<App.Platform.Data.GameScreenshot>((faker) => {
  return {
    id: faker.number.int({ min: 1, max: 99999 }),
    type: 'ingame',
    width: 256,
    height: 224,
    originalUrl: faker.internet.url(),
    smWebpUrl: faker.internet.url(),
    smAvifUrl: faker.internet.url(),
    mdWebpUrl: faker.internet.url(),
    mdAvifUrl: faker.internet.url(),
    lgWebpUrl: faker.internet.url(),
    lgAvifUrl: faker.internet.url(),
  };
});

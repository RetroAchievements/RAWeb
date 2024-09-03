import { createFactory } from '../createFactory';

export const createGameHashLabel = createFactory<App.Platform.Data.GameHashLabel>((faker) => ({
  imgSrc: faker.internet.url(),
  label: faker.helpers.arrayElement([
    'nointro',
    'rapatches',
    'fbneo',
    'goodtools',
    'redump',
    'mamesl',
    'tosec',
    'itchio',
    'msu1',
    'lostlevel',
  ]),
}));

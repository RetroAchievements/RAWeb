import { createFactory } from '../createFactory';

export const createGameRelease = createFactory<App.Platform.Data.GameRelease>((faker) => {
  return {
    id: faker.number.int({ min: 1, max: 99999 }),
    isCanonicalGameTitle: true,
    region: 'worldwide',
    releasedAt: faker.date.past().toISOString(),
    releasedAtGranularity: 'day',
    title: faker.word.words(4),
  };
});

import { createFactory } from '../createFactory';

export const createGameSet = createFactory<App.Platform.Data.GameSet>((faker) => {
  return {
    badgeUrl: faker.internet.url(),
    gameCount: faker.number.int({ min: 0, max: 200 }),
    hasMatureContent: false,
    id: faker.number.int({ min: 1, max: 99999 }),
    linkCount: faker.number.int({ min: 0, max: 200 }),
    title: faker.word.words(3),
    type: faker.helpers.arrayElement(['hub', 'similar-games']),
    updatedAt: faker.date.recent().toISOString(),
  };
});

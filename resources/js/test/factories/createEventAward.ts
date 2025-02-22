import { createFactory } from '../createFactory';

export const createEventAward = createFactory<App.Platform.Data.EventAward>((faker) => {
  return {
    badgeUrl: faker.internet.url(),
    earnedAt: null,
    eventId: faker.number.int({ min: 1, max: 10000 }),
    id: faker.number.int({ min: 1, max: 10000 }),
    label: faker.word.words(1),
    pointsRequired: 1,
    tierIndex: 0,
  };
});

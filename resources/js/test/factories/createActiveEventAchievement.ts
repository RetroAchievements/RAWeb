import { createFactory } from '@/test/createFactory';

export const createActiveEventAchievement = createFactory<App.Platform.Data.ActiveEventAchievement>(
  (faker) => {
    return {
      achievementId: faker.number.int({ min: 1, max: 99999 }),
      eventId: faker.number.int({ min: 1, max: 99999 }),
      eventTitle: faker.word.words(3),
      activeUntil: faker.date.future().toISOString(),
      userUnlocked: false,
    };
  },
);

import { createFactory } from '@/test/createFactory';

export const createActiveEventAchievement = createFactory<App.Platform.Data.ActiveEventAchievement>((faker) => {
  return {
    achievementId: faker.number.int({ min: 1, max: 99999 }),
    link: faker.internet.url(),
    summary: faker.word.words(8),
    userUnlocked: false,
  };
});

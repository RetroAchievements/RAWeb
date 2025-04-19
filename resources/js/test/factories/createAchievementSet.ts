import { createFactory } from '../createFactory';

export const createAchievementSet = createFactory<App.Platform.Data.AchievementSet>((faker) => {
  return {
    achievements: [],
    achievementsPublished: 0,
    achievementsUnpublished: 0,
    createdAt: faker.date.recent().toISOString(),
    id: faker.number.int({ min: 1, max: 999_999 }),
    imageAssetPathUrl: `https://retroachievements.org/media/000001.png`,
    playersHardcore: 0,
    playersTotal: 0,
    pointsTotal: 0,
    pointsWeighted: 0,
    updatedAt: faker.date.recent().toISOString(),
  };
});

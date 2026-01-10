import { createFactory } from '@/test/createFactory';

export const createAggregateAchievementSetCredits =
  createFactory<App.Platform.Data.AggregateAchievementSetCredits>(() => {
    return {
      achievementsArtwork: [],
      achievementsAuthors: [],
      achievementsDesign: [],
      achievementSetArtwork: [],
      achievementSetBanner: [],
      achievementsLogic: [],
      achievementsMaintainers: [],
      achievementsTesting: [],
      achievementsWriting: [],
      hashCompatibilityTesting: [],
    };
  });

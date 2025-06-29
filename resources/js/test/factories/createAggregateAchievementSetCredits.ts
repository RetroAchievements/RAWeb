import { createFactory } from '@/test/createFactory';

export const createAggregateAchievementSetCredits =
  createFactory<App.Platform.Data.AggregateAchievementSetCredits>(() => {
    return {
      achievementsArtwork: [],
      achievementsAuthors: [],
      achievementsDesign: [],
      achievementSetArtwork: [],
      achievementsLogic: [],
      achievementsMaintainers: [],
      achievementsTesting: [],
      achievementsWriting: [],
    };
  });

import { ClaimStatus } from '@/common/utils/generatedAppConstants';
import { createFactory } from '@/test/createFactory';

import { createAchievement } from '../createAchievement';
import { createAchievementSetClaim } from '../createAchievementSetClaim';
import { createNews } from '../createNews';
import { createRecentActiveForumTopic } from '../createRecentActiveForumTopic';
import { createStaticData } from './createStaticData';
import { createStaticGameAward } from './createStaticGameAward';

export const createHomePageProps = createFactory<App.Http.Data.HomePageProps>((faker) => {
  return {
    achievementOfTheWeek: createAchievement(),
    staticData: createStaticData(),
    mostRecentGameBeaten: createStaticGameAward(),
    mostRecentGameMastered: createStaticGameAward(),
    recentNews: [createNews(), createNews(), createNews()],

    completedClaims: [
      createAchievementSetClaim({ status: ClaimStatus.Complete }),
      createAchievementSetClaim({ status: ClaimStatus.Complete }),
      createAchievementSetClaim({ status: ClaimStatus.Complete }),
      createAchievementSetClaim({ status: ClaimStatus.Complete }),
      createAchievementSetClaim({ status: ClaimStatus.Complete }),
      createAchievementSetClaim({ status: ClaimStatus.Complete }),
    ],

    newClaims: [
      createAchievementSetClaim({ status: ClaimStatus.Active }),
      createAchievementSetClaim({ status: ClaimStatus.Active }),
      createAchievementSetClaim({ status: ClaimStatus.Active }),
      createAchievementSetClaim({ status: ClaimStatus.Active }),
      createAchievementSetClaim({ status: ClaimStatus.Active }),
    ],

    currentlyOnline: {
      allTimeHighDate: faker.date.recent().toISOString(),
      allTimeHighPlayers: faker.number.int({ min: 2000, max: 4000 }),
      logEntries: [],
      numCurrentPlayers: faker.number.int({ min: 3000 }),
    },

    recentForumPosts: [
      createRecentActiveForumTopic(),
      createRecentActiveForumTopic(),
      createRecentActiveForumTopic(),
      createRecentActiveForumTopic(),
    ],
  };
});

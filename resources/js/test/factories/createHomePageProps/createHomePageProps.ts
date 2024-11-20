import { ClaimStatus } from '@/common/utils/generatedAppConstants';
import { createFactory } from '@/test/createFactory';

import { createAchievementSetClaim } from '../createAchievementSetClaim';
import { createActivePlayer } from '../createActivePlayer';
import { createEventAchievement } from '../createEventAchievement';
import { createNews } from '../createNews';
import { createPaginatedData } from '../createPaginatedData';
import { createRecentActiveForumTopic } from '../createRecentActiveForumTopic';
import { createTrendingGame } from '../createTrendingGame';
import { createStaticData } from './createStaticData';
import { createStaticGameAward } from './createStaticGameAward';

export const createHomePageProps = createFactory<App.Http.Data.HomePageProps>((faker) => {
  return {
    achievementOfTheWeek: createEventAchievement(),
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

    activePlayers: createPaginatedData(
      [createActivePlayer(), createActivePlayer(), createActivePlayer(), createActivePlayer()],
      { total: 4, unfilteredTotal: 4, currentPage: 1, lastPage: 1, perPage: 20 },
    ),

    trendingGames: [
      createTrendingGame(),
      createTrendingGame(),
      createTrendingGame(),
      createTrendingGame(),
    ],

    persistedActivePlayersSearch: null,

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

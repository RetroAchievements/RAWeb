import { createFactory } from '@/test/createFactory';

import { createAchievementSetClaimGroup } from '../createAchievementSetClaimGroup';
import { createActivePlayer } from '../createActivePlayer';
import { createGameActivitySnapshot } from '../createGameActivitySnapshot';
import { createNews } from '../createNews';
import { createPaginatedData } from '../createPaginatedData';
import { createRecentActiveForumTopic } from '../createRecentActiveForumTopic';
import { createAchievementOfTheWeekProps } from './createAchievementOfTheWeekProps';
import { createStaticData } from './createStaticData';
import { createStaticGameAward } from './createStaticGameAward';

export const createHomePageProps = createFactory<App.Http.Data.HomePageProps>((faker) => {
  return {
    achievementOfTheWeek: createAchievementOfTheWeekProps(),
    staticData: createStaticData(),
    mostRecentGameBeaten: createStaticGameAward(),
    mostRecentGameMastered: createStaticGameAward(),
    recentNews: [createNews(), createNews(), createNews()],

    completedClaims: [
      createAchievementSetClaimGroup({ status: 'complete' }),
      createAchievementSetClaimGroup({ status: 'complete' }),
      createAchievementSetClaimGroup({ status: 'complete' }),
      createAchievementSetClaimGroup({ status: 'complete' }),
      createAchievementSetClaimGroup({ status: 'complete' }),
      createAchievementSetClaimGroup({ status: 'complete' }),
    ],

    newClaims: [
      createAchievementSetClaimGroup({ status: 'active' }),
      createAchievementSetClaimGroup({ status: 'active' }),
      createAchievementSetClaimGroup({ status: 'active' }),
      createAchievementSetClaimGroup({ status: 'active' }),
      createAchievementSetClaimGroup({ status: 'active' }),
    ],

    activePlayers: createPaginatedData(
      [createActivePlayer(), createActivePlayer(), createActivePlayer(), createActivePlayer()],
      { total: 4, unfilteredTotal: 4, currentPage: 1, lastPage: 1, perPage: 20 },
    ),

    trendingGames: [
      createGameActivitySnapshot(),
      createGameActivitySnapshot(),
      createGameActivitySnapshot(),
      createGameActivitySnapshot(),
    ],

    popularGames: [
      createGameActivitySnapshot(),
      createGameActivitySnapshot(),
      createGameActivitySnapshot(),
      createGameActivitySnapshot(),
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

    userCurrentGame: null,
    userCurrentGameMinutesAgo: null,

    hasSiteReleaseNotes: false,
    hasUnreadSiteReleaseNote: false,
    deferredSiteReleaseNotes: [],
  };
});

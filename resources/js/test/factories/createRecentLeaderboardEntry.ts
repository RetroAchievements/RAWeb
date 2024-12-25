import { createFactory } from '../createFactory';
import { createGame } from './createGame';
import { createLeaderboard } from './createLeaderboard';
import { createLeaderboardEntry } from './createLeaderboardEntry';
import { createUser } from './createUser';

export const createRecentLeaderboardEntry =
  createFactory<App.Community.Data.RecentLeaderboardEntry>((faker) => {
    const game = createGame();
    const leaderboard = createLeaderboard({ game });

    return {
      game,
      leaderboard,
      leaderboardEntry: createLeaderboardEntry(),
      submittedAt: faker.date.recent().toISOString(),
      user: createUser(),
    };
  });

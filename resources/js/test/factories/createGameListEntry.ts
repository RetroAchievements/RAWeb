import { createFactory } from '../createFactory';
import { createGame } from './createGame';
import { createGameListEntryStats } from './createGameListEntryStats';
import { createPlayerGame } from './createPlayerGame';

export const createGameListEntry = createFactory<App.Platform.Data.GameListEntry>((faker) => {
  return {
    game: createGame(),
    playerGame: createPlayerGame(),
    isInBacklog: faker.datatype.boolean(),
    gameListStats: createGameListEntryStats(),
  };
});

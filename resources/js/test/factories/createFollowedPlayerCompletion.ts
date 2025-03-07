import { createFactory } from '../createFactory';
import { createPlayerGame } from './createPlayerGame';
import { createUser } from './createUser';

export const createFollowedPlayerCompletion =
  createFactory<App.Platform.Data.FollowedPlayerCompletion>(() => {
    return {
      playerGame: createPlayerGame(),
      user: createUser(),
    };
  });

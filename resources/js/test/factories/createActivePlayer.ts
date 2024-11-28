import { createFactory } from '@/test/createFactory';

import { createGame } from './createGame';
import { createUser } from './createUser';

export const createActivePlayer = createFactory<App.Community.Data.ActivePlayer>(() => {
  return {
    game: createGame(),
    user: createUser(),
  };
});

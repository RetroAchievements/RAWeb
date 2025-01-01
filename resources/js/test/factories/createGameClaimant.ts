import { createFactory } from '../createFactory';
import { createUser } from './createUser';

export const createGameClaimant = createFactory<App.Platform.Data.GameClaimant>(() => {
  return {
    claimType: 'primary',
    user: createUser(),
  };
});

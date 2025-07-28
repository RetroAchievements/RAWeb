import { createFactory } from '../createFactory';

export const createGameSetRequestData = createFactory<App.Platform.Data.GameSetRequestData>(() => {
  return {
    hasUserRequestedSet: false,
    totalRequests: 42,
    userRequestsRemaining: 5,
  };
});

import { createFactory } from '../createFactory';

export const createGamePageClaimData = createFactory<App.Platform.Data.GamePageClaimData>(() => {
  return {
    numUnresolvedTickets: 0,
    userClaim: null,
    isSoleAuthor: false,
    wouldBeCollaboration: false,
    wouldBeRevision: false,
  };
});

import { createFactory } from '../createFactory';

export const createGamePageClaimData = createFactory<App.Platform.Data.GamePageClaimData>(() => {
  return {
    doesPrimaryClaimExist: false,
    maxClaimCount: 0,
    numClaimsRemaining: 0,
    numUnresolvedTickets: 0,
    userClaim: null,
    isSoleAuthor: false,
    wouldBeCollaboration: false,
    wouldBeRevision: false,
  };
});

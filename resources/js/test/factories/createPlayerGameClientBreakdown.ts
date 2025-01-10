import { createFactory } from '../createFactory';

export const createPlayerGameClientBreakdown =
  createFactory<App.Platform.Data.PlayerGameClientBreakdown>(() => {
    return {
      agents: [],
      clientIdentifier: '',
      duration: 3600,
      durationPercentage: 1,
    };
  });

import { createFactory } from '../createFactory';

export const createSetUserRequestInfo = createFactory<App.Platform.Data.UserSetRequestInfo>(() => {
  return {
    pointsForNext: 1000,
    remaining: 0,
    total: 10,
    used: 10,
  };
});

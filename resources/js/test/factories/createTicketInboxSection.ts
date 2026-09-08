import { createFactory } from '../createFactory';

export const createTicketInboxSection = createFactory<App.Platform.Data.TicketInboxSection>(() => {
  return {
    kind: 'toResolve',
    count: 0,
    tickets: [],
  };
});

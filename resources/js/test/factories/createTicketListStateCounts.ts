import { createFactory } from '../createFactory';

export const createTicketListStateCounts = createFactory<App.Platform.Data.TicketListStateCounts>(
  (faker) => {
    const open = faker.number.int({ min: 0, max: 200 });
    const request = faker.number.int({ min: 0, max: 50 });
    const unresolved = open + request;
    const resolved = faker.number.int({ min: 0, max: 200 });
    const closed = faker.number.int({ min: 0, max: 50 });
    const quarantined = faker.number.int({ min: 0, max: 20 });

    return {
      unresolved,
      open,
      request,
      resolved,
      closed,
      quarantined,
      all: unresolved + resolved + closed + quarantined,
    };
  },
);

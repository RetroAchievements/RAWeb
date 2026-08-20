import { createFactory } from '../createFactory';

export const createTicketListStateCounts = createFactory<App.Platform.Data.TicketListStateCounts>(
  (faker) => {
    const request = faker.number.int({ min: 0, max: 50 });
    const unresolved = request + faker.number.int({ min: 0, max: 200 });
    const resolved = faker.number.int({ min: 0, max: 200 });
    const quarantined = faker.number.int({ min: 0, max: 20 });

    return {
      unresolved,
      request,
      resolved,
      quarantined,
      all: unresolved + resolved + quarantined,
    };
  },
);

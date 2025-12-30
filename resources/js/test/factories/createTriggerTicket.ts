import { createFactory } from '@/test/createFactory';

import { createAchievement } from './createAchievement';

export const createTriggerTicket = createFactory<App.Platform.Data.TriggerTicket>((faker) => {
  return {
    id: faker.number.int({ min: 1, max: 99999 }),
    ticketableType: 'achievement',
    ticketable: createAchievement(),
  };
});

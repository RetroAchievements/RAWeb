import { createFactory } from '../createFactory';
import { createAchievement } from './createAchievement';

export const createPlayerGameActivityEvent =
  createFactory<App.Platform.Data.PlayerGameActivityEvent>((faker) => {
    return {
      type: 'unlock',
      description: null,
      header: null,
      when: faker.date.recent().toISOString(),
      id: faker.number.int({ min: 1, max: 999_999 }),
      hardcore: faker.datatype.boolean(),
      achievement: createAchievement(),
      unlocker: null,
      hardcoreLater: false,
    };
  });

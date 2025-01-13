import { createFactory } from '../createFactory';
import { createAchievement } from './createAchievement';
import { createRaEvent } from './createRaEvent';

export const createEventAchievement = createFactory<App.Platform.Data.EventAchievement>((faker) => {
  return {
    achievement: createAchievement(),
    sourceAchievement: createAchievement(),
    forumTopicId: faker.number.int({ min: 0, max: 99999 }),
    event: createRaEvent(),
  };
});

import { createFactory } from '../createFactory';
import { createAchievement } from './createAchievement';

export const createEventAchievement = createFactory<App.Platform.Data.EventAchievement>((faker) => {
  return {
    achievement: createAchievement(),
    sourceAchievement: createAchievement(),
    forumTopicId: faker.number.int({ min: 0, max: 99999 }),
  };
});

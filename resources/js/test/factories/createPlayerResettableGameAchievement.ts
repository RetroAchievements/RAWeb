import { createFactory } from '../createFactory';

export const createPlayerResettableGameAchievement =
  createFactory<App.Platform.Data.PlayerResettableGameAchievement>((faker) => ({
    id: faker.number.int({ min: 1, max: 99999 }),
    title: faker.word.words(2),
    points: faker.number.int({ min: 1, max: 100 }),
    isHardcore: faker.datatype.boolean(),
  }));

import { createFactory } from '../createFactory';

export const createAchievementChangelogEntry =
  createFactory<App.Platform.Data.AchievementChangelogEntry>((faker) => {
    return {
      type: faker.helpers.arrayElement([
        'created',
        'edited',
        'promoted',
        'demoted',
        'description-updated',
        'title-updated',
        'badge-updated',
        'logic-updated',
      ] as App.Platform.Enums.AchievementChangelogEntryType[]),
      createdAt: faker.date.past().toISOString(),
      user: null,
      fieldChanges: [],
      count: 1,
    };
  });

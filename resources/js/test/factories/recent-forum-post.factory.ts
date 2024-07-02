import { faker } from '@faker-js/faker';

import type { RecentForumPost } from '@/forums/models';

export function createRecentForumPost(props?: Partial<RecentForumPost>): RecentForumPost {
  return {
    forumTopicId: faker.number.int({ min: 1, max: 30000 }),
    forumTopicTitle: faker.word.words(5),
    commentId: faker.number.int({ min: 1, max: 30000 }),
    postedAt: faker.date.recent().toISOString(),
    authorDisplayName: faker.internet.displayName(),
    shortMessage: faker.word.words(20),
    commentIdDay: faker.number.int({ min: 1, max: 30000 }),
    commentCountDay: faker.number.int({ min: 0, max: 5 }),
    commentIdWeek: faker.number.int({ min: 1, max: 30000 }),
    commentCountWeek: faker.number.int({ min: 0, max: 5 }),

    ...props,
  };
}

import type { SetRequired } from 'type-fest';

import { createFactory } from '@/test/createFactory';
import { createUser } from '@/test/factories';
import { createForumTopicComment } from '@/test/factories/createForumTopicComment';

export type RecentActiveForumTopic = SetRequired<App.Data.ForumTopic, 'latestComment'>;

export const createRecentActiveForumTopic = createFactory<RecentActiveForumTopic>((faker) => ({
  commentCount24h: faker.number.int({ min: 0, max: 3 }),
  commentCount7d: faker.number.int({ min: 0, max: 3 }),
  createdAt: faker.date.recent().toISOString(),
  id: faker.number.int({ min: 0, max: 999999 }),
  oldestComment24hId: faker.number.int({ min: 0, max: 9999 }),
  oldestComment7dId: faker.number.int({ min: 0, max: 9999 }),
  title: faker.word.words(4),
  user: createUser(),
  latestComment: createForumTopicComment(),
}));

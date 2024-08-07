import type { AppGlobalProps, PaginatedData } from '@/common/models';

import type { RecentActiveForumTopic } from './recent-active-forum-topic.model';

export interface RecentPostsPageProps extends AppGlobalProps {
  paginatedTopics: PaginatedData<RecentActiveForumTopic>;
}

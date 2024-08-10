import type { AppGlobalProps } from '@/common/models';

import type { RecentForumPost } from './recent-forum-post.model';

export interface RecentPostsPageProps extends AppGlobalProps {
  maxPerPage: number;
  nextPageUrl: string;
  previousPageUrl: string | null;
  recentForumPosts: RecentForumPost[];
}

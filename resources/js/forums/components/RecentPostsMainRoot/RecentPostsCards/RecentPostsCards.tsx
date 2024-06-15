import { usePage } from '@inertiajs/react';
import type { FC } from 'react';

import { UserAvatar } from '@/common/components/UserAvatar';
import type { RecentPostsPageProps } from '@/forums/models';

import { AggregateRecentPostLinks } from '../AggregateRecentPostLinks';
import { PostTimestamp } from '../PostTimestamp';

export const RecentPostsCards: FC = () => {
  const { props } = usePage<RecentPostsPageProps>();

  const { recentForumPosts, auth } = props;

  return (
    <div className="flex flex-col gap-y-2">
      {recentForumPosts.map((recentForumPost) => (
        <div key={`card-${recentForumPost.commentId}`} className="embedded">
          <div className="relative flex justify-between">
            <div className="flex flex-col gap-y-1">
              <UserAvatar displayName={recentForumPost.authorDisplayName} size={16} />
              <span className="smalldate">
                <PostTimestamp
                  asAbsoluteDate={auth?.user.preferences.prefersAbsoluteDates ?? false}
                  postedAt={recentForumPost.postedAt}
                />
              </span>
            </div>

            <AggregateRecentPostLinks recentForumPost={recentForumPost} />
          </div>

          <div className="flex flex-col gap-y-2">
            <p className="truncate">
              in{' '}
              <a
                href={`/viewtopic.php?t=${recentForumPost.forumTopicId}&c=${recentForumPost.commentId}#${recentForumPost.commentId}`}
              >
                {recentForumPost.forumTopicTitle}
              </a>
            </p>

            <p className="line-clamp-3 text-xs">{recentForumPost.shortMessage}</p>
          </div>
        </div>
      ))}
    </div>
  );
};

import { usePage } from '@inertiajs/react';
import type { FC } from 'react';

import { UserAvatar } from '@/common/components/UserAvatar';
import type { RecentPostsPageProps } from '@/forums/models';

import { AggregateRecentPostLinks } from '../AggregateRecentPostLinks';
import { PostTimestamp } from '../PostTimestamp';

export const RecentPostsTable: FC = () => {
  const { props } = usePage<RecentPostsPageProps>();

  const { recentForumPosts, auth } = props;

  return (
    <table className="table-highlight">
      <thead>
        <tr className="do-not-highlight">
          <th>Last Post By</th>
          <th>Message</th>
          <th className="whitespace-nowrap text-right">Additional Posts</th>
        </tr>
      </thead>

      <tbody>
        {recentForumPosts.map((recentForumPost) => (
          <tr key={recentForumPost.commentId}>
            <td className="py-3">
              <UserAvatar displayName={recentForumPost.authorDisplayName} size={24} />
            </td>

            <td>
              <p className="flex items-center gap-x-2">
                <a
                  href={`/viewtopic.php?t=${recentForumPost.forumTopicId}&c=${recentForumPost.commentId}#${recentForumPost.commentId}`}
                >
                  {recentForumPost.forumTopicTitle}
                </a>
                <span className="smalldate">
                  <PostTimestamp
                    asAbsoluteDate={auth?.user.preferences.prefersAbsoluteDates ?? false}
                    postedAt={recentForumPost.postedAt}
                  />
                </span>
              </p>

              <div className="comment text-overflow-wrap">
                <p className="lg:line-clamp-2 xl:line-clamp-1">{recentForumPost.shortMessage}</p>
              </div>
            </td>

            <td>
              <AggregateRecentPostLinks recentForumPost={recentForumPost} />
            </td>
          </tr>
        ))}
      </tbody>
    </table>
  );
};

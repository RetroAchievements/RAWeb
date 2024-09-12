import type { FC } from 'react';

import { UserAvatar } from '@/common/components/UserAvatar';
import { usePageProps } from '@/common/hooks/usePageProps';

import { PostTimestamp } from '../PostTimestamp';
import { RecentPostAggregateLinks } from '../RecentPostAggregateLinks';

interface RecentPostsTableProps {
  paginatedTopics: App.Data.PaginatedData<App.Data.ForumTopic>;

  showAdditionalPosts?: boolean;
  showLastPostBy?: boolean;
}

export const RecentPostsTable: FC<RecentPostsTableProps> = ({
  paginatedTopics,
  showAdditionalPosts = true,
  showLastPostBy = true,
}) => {
  const { auth } = usePageProps();

  return (
    <table className="table-highlight">
      <thead>
        <tr className="do-not-highlight">
          {showLastPostBy ? <th>Last Post By</th> : null}

          <th>Message</th>

          {showAdditionalPosts ? (
            <th className="whitespace-nowrap text-right">Additional Posts</th>
          ) : null}
        </tr>
      </thead>

      <tbody>
        {paginatedTopics.items.map((topic) => (
          <tr key={topic.latestComment?.id}>
            <td className="py-3">
              {showLastPostBy && topic.latestComment?.user ? (
                <UserAvatar {...topic.latestComment.user} size={24} />
              ) : null}
            </td>

            <td className="py-2">
              <p className="flex items-center gap-x-2">
                <a
                  href={`/viewtopic.php?t=${topic.id}&c=${topic.latestComment?.id}#${topic.latestComment?.id}`}
                >
                  {topic.title}
                </a>
                <span className="smalldate">
                  {topic.latestComment?.createdAt ? (
                    <PostTimestamp
                      asAbsoluteDate={auth?.user.preferences.prefersAbsoluteDates ?? false}
                      postedAt={topic.latestComment.createdAt}
                    />
                  ) : null}
                </span>
              </p>

              <div className="comment text-overflow-wrap">
                <p className="lg:line-clamp-2 xl:line-clamp-1">{topic.latestComment?.body}</p>
              </div>
            </td>

            {showAdditionalPosts ? (
              <td className="text-right">
                <RecentPostAggregateLinks topic={topic} />
              </td>
            ) : null}
          </tr>
        ))}
      </tbody>
    </table>
  );
};

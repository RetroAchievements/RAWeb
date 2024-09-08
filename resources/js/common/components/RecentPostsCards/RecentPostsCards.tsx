import type { FC } from 'react';

import { UserAvatar } from '@/common/components/UserAvatar';
import { usePageProps } from '@/common/hooks/usePageProps';

import { PostTimestamp } from '../PostTimestamp';
import { RecentPostAggregateLinks } from '../RecentPostAggregateLinks';

interface RecentPostsCardsProps {
  paginatedTopics: App.Data.PaginatedData<App.Data.ForumTopic>;

  showUser?: boolean;
}

export const RecentPostsCards: FC<RecentPostsCardsProps> = ({
  paginatedTopics,
  showUser = true,
}) => {
  const { auth } = usePageProps<App.Community.Data.RecentPostsPageProps>();

  return (
    <div className="flex flex-col gap-y-2">
      {paginatedTopics.items.map((topic) => (
        <div key={`card-${topic?.latestComment?.id}`} className="embedded">
          <div className="relative flex justify-between">
            <div className="flex flex-col gap-y-1">
              {showUser && topic.latestComment?.user ? (
                <UserAvatar {...topic.latestComment.user} size={16} />
              ) : null}

              {topic.latestComment?.createdAt ? (
                <span className="smalldate">
                  <PostTimestamp
                    asAbsoluteDate={auth?.user.preferences.prefersAbsoluteDates ?? false}
                    postedAt={topic.latestComment.createdAt}
                  />
                </span>
              ) : null}
            </div>

            <RecentPostAggregateLinks topic={topic} />
          </div>

          <div className="flex flex-col gap-y-2">
            <p className="truncate">
              in{' '}
              <a
                href={`/viewtopic.php?t=${topic.id}&c=${topic.latestComment?.id}#${topic.latestComment?.id}`}
              >
                {topic.title}
              </a>
            </p>

            <p className="line-clamp-3 text-xs">{topic.latestComment?.body}</p>
          </div>
        </div>
      ))}
    </div>
  );
};

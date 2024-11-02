import type { FC } from 'react';

import { UserAvatar } from '@/common/components/UserAvatar';
import { usePageProps } from '@/common/hooks/usePageProps';

import { DiffTimestamp } from '../DiffTimestamp';
import { RecentPostAggregateLinks } from '../RecentPostAggregateLinks';
import { Trans } from '../Trans';

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
                <span className="smalldate" data-testid="timestamp">
                  <DiffTimestamp
                    asAbsoluteDate={auth?.user.preferences.prefersAbsoluteDates ?? false}
                    at={topic.latestComment.createdAt}
                  />
                </span>
              ) : null}
            </div>

            <RecentPostAggregateLinks topic={topic} />
          </div>

          <div className="flex flex-col gap-y-2">
            <p className="truncate">
              <Trans i18nKey="in <0>:forumTopicTitle</0>" values={{ forumTopicTitle: topic.title }}>
                {/* eslint-disable react/jsx-no-literals */}
                in{' '}
                <a
                  href={`/viewtopic.php?t=${topic.id}&c=${topic.latestComment?.id}#${topic.latestComment?.id}`}
                >
                  {topic.title}
                </a>
                {/* eslint-enable react/jsx-no-literals */}
              </Trans>
            </p>

            <p className="line-clamp-3 text-xs">{topic.latestComment?.body}</p>
          </div>
        </div>
      ))}
    </div>
  );
};

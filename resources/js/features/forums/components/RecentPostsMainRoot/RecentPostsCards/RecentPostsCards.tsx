import { useLaravelReactI18n } from 'laravel-react-i18n';
import type { FC } from 'react';

import { UserAvatar } from '@/common/components/UserAvatar';
import { usePageProps } from '@/common/hooks/usePageProps';

import { AggregateRecentPostLinks } from '../AggregateRecentPostLinks';
import { PostTimestamp } from '../PostTimestamp';

export const RecentPostsCards: FC = () => {
  const { auth, paginatedTopics } = usePageProps<App.Community.Data.RecentPostsPageProps>();

  const { t } = useLaravelReactI18n();

  return (
    <div className="flex flex-col gap-y-2">
      {paginatedTopics.items.map((topic) => (
        <div key={`card-${topic?.latestComment?.id}`} className="embedded">
          <div className="relative flex justify-between">
            <div className="flex flex-col gap-y-1">
              <UserAvatar displayName={topic?.latestComment?.user.displayName ?? ''} size={16} />

              {topic.latestComment?.createdAt ? (
                <span className="smalldate">
                  <PostTimestamp
                    asAbsoluteDate={auth?.user.preferences.prefersAbsoluteDates ?? false}
                    postedAt={topic.latestComment.createdAt}
                  />
                </span>
              ) : null}
            </div>

            <AggregateRecentPostLinks topic={topic} />
          </div>

          <div className="flex flex-col gap-y-2">
            <p className="truncate">
              {t('in')}{' '}
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

import { useLaravelReactI18n } from 'laravel-react-i18n';
import type { FC } from 'react';

import { UserAvatar } from '@/common/components/UserAvatar';
import { usePageProps } from '@/common/hooks/usePageProps';

import { AggregateRecentPostLinks } from '../AggregateRecentPostLinks';
import { PostTimestamp } from '../PostTimestamp';

export const RecentPostsTable: FC = () => {
  const { auth, paginatedTopics } = usePageProps<App.Community.Data.RecentPostsPageProps>();

  const { t } = useLaravelReactI18n();

  return (
    <table className="table-highlight">
      <thead>
        <tr className="do-not-highlight">
          <th>{t('Last Post By')}</th>
          <th>{t('Message')}</th>
          <th className="whitespace-nowrap text-right">{t('Additional Posts')}</th>
        </tr>
      </thead>

      <tbody>
        {paginatedTopics.items.map((topic) => (
          <tr key={topic.latestComment?.id}>
            <td className="py-3">
              <UserAvatar displayName={topic.latestComment?.user.displayName ?? ''} size={24} />
            </td>

            <td>
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

            <td className="text-right">
              <AggregateRecentPostLinks topic={topic} />
            </td>
          </tr>
        ))}
      </tbody>
    </table>
  );
};

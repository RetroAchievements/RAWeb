import type { FC } from 'react';
import { useTranslation } from 'react-i18next';

import { InertiaLink } from '../InertiaLink';

interface RecentPostAggregateLinksProps {
  topic: App.Data.ForumTopic;
}

export const RecentPostAggregateLinks: FC<RecentPostAggregateLinksProps> = ({ topic }) => {
  const { t } = useTranslation();

  const { commentCount24h, commentCount7d, oldestComment24hId, oldestComment7dId, id } = topic;

  if (!commentCount7d || commentCount7d <= 1) {
    return null;
  }

  const canShowDailyPostCount = commentCount24h && commentCount24h > 1;
  const canShowWeeklyPostCount = commentCount7d && commentCount7d > (commentCount24h ?? 0);

  return (
    <div className="smalltext whitespace-nowrap">
      <div className="flex flex-col gap-y-1">
        {canShowDailyPostCount ? (
          <InertiaLink
            href={
              route('forum-topic.show', { topic: id, comment: oldestComment24hId }) +
              `#${oldestComment24hId}`
            }
          >
            {t('{{count, number}} posts in the last 24 hours', { count: commentCount24h })}
          </InertiaLink>
        ) : null}

        {canShowWeeklyPostCount ? (
          <InertiaLink
            href={
              route('forum-topic.show', { topic: id, comment: oldestComment7dId }) +
              `#${oldestComment7dId}`
            }
          >
            {t('{{count, number}} posts in the last 7 days', { count: commentCount7d })}
          </InertiaLink>
        ) : null}
      </div>
    </div>
  );
};

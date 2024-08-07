import type { FC } from 'react';

import type { RecentActiveForumTopic } from '@/features/forums/models';

interface AggregateRecentPostLinksProps {
  topic: RecentActiveForumTopic;
}

export const AggregateRecentPostLinks: FC<AggregateRecentPostLinksProps> = ({ topic }) => {
  const { commentCount24h, commentCount7d, oldestComment24hId, oldestComment7dId, id } = topic;

  if (!commentCount7d || commentCount7d <= 1) {
    return null;
  }

  const canShowDailyPostCount = commentCount24h && commentCount24h > 1;
  const canShowWeeklyPostCount =
    commentCount24h && commentCount7d && commentCount7d > commentCount24h;

  return (
    <div className="smalltext whitespace-nowrap">
      <div className="flex flex-col gap-y-1">
        {canShowDailyPostCount ? (
          <a href={`/viewtopic.php?t=${id}&c=${oldestComment24hId}#${oldestComment24hId}`}>
            {commentCount24h} posts in the last 24 hours
          </a>
        ) : null}

        {canShowWeeklyPostCount ? (
          <a href={`/viewtopic.php?t=${id}&c=${oldestComment7dId}#${oldestComment7dId}`}>
            {commentCount7d} posts in the last 7 days
          </a>
        ) : null}
      </div>
    </div>
  );
};

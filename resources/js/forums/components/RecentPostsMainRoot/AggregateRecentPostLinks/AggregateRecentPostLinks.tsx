import type { FC } from 'react';

import type { RecentForumPost } from '@/forums/models';

interface AggregateRecentPostLinksProps {
  recentForumPost: RecentForumPost;
}

export const AggregateRecentPostLinks: FC<AggregateRecentPostLinksProps> = ({
  recentForumPost,
}) => {
  const { commentCountDay, commentCountWeek, commentIdDay, commentIdWeek, forumTopicId } =
    recentForumPost;

  if (!commentCountWeek || commentCountWeek <= 1) {
    return null;
  }

  const canShowDailyPostCount = commentCountDay && commentCountDay > 1;
  const canShowWeeklyPostCount =
    commentCountDay && commentCountWeek && commentCountWeek > commentCountDay;

  return (
    <div className="smalltext whitespace-nowrap">
      <div className="flex flex-col gap-y-1">
        {canShowDailyPostCount ? (
          <a href={`/viewtopic.php?t=${forumTopicId}&c=${commentIdDay}#${commentIdDay}`}>
            {commentCountDay} posts in the last 24 hours
          </a>
        ) : null}

        {canShowWeeklyPostCount ? (
          <a href={`/viewtopic.php?t=${forumTopicId}&c=${commentIdWeek}#${commentIdWeek}`}>
            {commentCountWeek} posts in the last 7 days
          </a>
        ) : null}
      </div>
    </div>
  );
};

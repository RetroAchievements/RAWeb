import * as motion from 'motion/react-m';
import type { FC } from 'react';
import { LuChartBar, LuCrown } from 'react-icons/lu';

import { UserAvatar } from '@/common/components/UserAvatar';

interface LeaderboardsListItemProps {
  index: number;
  isLargeList: boolean;
  leaderboard: App.Platform.Data.Leaderboard;
}

export const LeaderboardsListItem: FC<LeaderboardsListItemProps> = ({
  index,
  isLargeList,
  leaderboard,
}) => {
  return (
    <motion.li
      className="flex w-full gap-x-3.5 px-2 py-3 odd:bg-[rgba(50,50,50,0.4)] light:odd:bg-neutral-100 md:gap-x-3 md:py-1"
      initial={{ opacity: 0, y: 10 }}
      animate={{ opacity: 1, y: 0 }}
      exit={{ opacity: 0, y: 10 }}
      transition={{
        duration: 0.12,
        delay: isLargeList
          ? Math.min(index * 0.008, 0.15) // Cap at 150ms for large lists
          : Math.min(index * 0.015, 0.2), // Cap at 200ms for small lists
      }}
    >
      <div className="flex flex-col gap-y-1 md:mt-1">
        {/* Icon */}
        <a
          href={`/leaderboardinfo.php?i=${leaderboard.id}`}
          className="flex size-16 items-center justify-center rounded bg-embed light:border light:border-neutral-300 light:bg-white"
        >
          <LuChartBar className="size-6" />
        </a>
      </div>

      <div className="grid w-full gap-x-5 gap-y-1.5 pb-2.5 leading-4 md:grid-cols-6">
        <div className="md:col-span-4 md:mt-1">
          {/* Title */}
          <div className="mb-0.5 md:mt-0">
            <span className="mr-2">
              <a href={`/leaderboardinfo.php?i=${leaderboard.id}`} className="font-medium">
                {leaderboard.title}
              </a>
            </span>
          </div>

          {/* Description */}
          <p className="leading-4">{leaderboard.description}</p>

          {/* Top entry */}
          <div className="mt-3">
            {leaderboard.topEntry?.user ? (
              <div className="flex items-center gap-3">
                <LuCrown className="size-4 text-yellow-400 light:text-amber-600" />

                <span className="text-neutral-300 light:text-neutral-700">
                  {leaderboard.topEntry.formattedScore}
                </span>

                <UserAvatar {...leaderboard.topEntry.user} size={16} labelClassName="-ml-0.5" />
              </div>
            ) : null}
          </div>
        </div>
      </div>
    </motion.li>
  );
};

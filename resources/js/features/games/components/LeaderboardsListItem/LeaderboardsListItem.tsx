import * as motion from 'motion/react-m';
import type { FC } from 'react';
import { useTranslation } from 'react-i18next';
import { LuChartBar, LuCrown, LuX } from 'react-icons/lu';

import {
  BaseTooltip,
  BaseTooltipContent,
  BaseTooltipTrigger,
} from '@/common/components/+vendor/BaseTooltip';
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
  const { t } = useTranslation();

  return (
    <motion.li
      className="game-set-item"
      initial={{ opacity: 0, transform: 'translateY(10px)' }}
      animate={{ opacity: 1, transform: 'translateY(0px)' }}
      exit={{ opacity: 0, transform: 'translateY(10px)' }}
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
          className="group flex size-16 flex-col items-center justify-center gap-0.5 rounded bg-embed light:border light:border-neutral-300 light:bg-white"
        >
          {leaderboard.state === 'active' ? (
            <LuChartBar className="size-6" />
          ) : (
            <BaseTooltip>
              <BaseTooltipTrigger asChild>
                <LuX className="size-6 text-neutral-500 transition-colors group-hover:text-white light:text-neutral-400 light:group-hover:text-black" />
              </BaseTooltipTrigger>
              <BaseTooltipContent>
                <div className="max-w-xs items-center text-center">
                  {t('This leaderboard is currently disabled and not accepting new entries.')}
                </div>
              </BaseTooltipContent>
            </BaseTooltip>
          )}
        </a>
      </div>

      <div className="grid w-full gap-x-5 gap-y-1.5 pb-2.5 leading-4 sm:grid-cols-6">
        <div className="sm:col-span-4 md:mt-1">
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
          <div className="mt-2.5">
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

        {/* User entry */}
        {leaderboard.userEntry ? (
          <div className="flex flex-col gap-1 sm:col-span-2 sm:mt-1 sm:items-end sm:justify-center">
            <div className="flex items-center justify-between sm:flex-col sm:items-end sm:justify-normal sm:gap-0.5">
              <p className="sm:text-lg">
                <span className="text-neutral-300 light:text-neutral-700">
                  {t('#{{rank, number}}', { rank: leaderboard.userEntry.rank })}
                </span>
                {' · '}
                {leaderboard.userEntry.formattedScore}
              </p>
            </div>
          </div>
        ) : null}
      </div>
    </motion.li>
  );
};

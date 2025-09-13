import * as motion from 'motion/react-m';
import type { FC } from 'react';
import { useTranslation } from 'react-i18next';
import { LuChartBar, LuCrown } from 'react-icons/lu';

import { UserAvatar } from '@/common/components/UserAvatar';
import { useFormatPercentage } from '@/common/hooks/useFormatPercentage';

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

  const { formatPercentage } = useFormatPercentage();

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
              <p className="text-neutral-400 light:text-neutral-700">{t('Your Position')}</p>

              <p className="sm:text-lg">
                <span className="text-neutral-300 light:text-neutral-700">
                  {t('#{{rank, number}}', { rank: leaderboard.userEntry.rank })}
                </span>
                {' / '}
                {leaderboard.userEntry.formattedScore}
              </p>
            </div>

            {leaderboard.userEntry.percentageOfTopScore ? (
              <p className="mt-0.5 hidden text-xs text-neutral-500 sm:block">
                {t('{{percentage}} of #1', {
                  percentage: formatPercentage(leaderboard.userEntry.percentageOfTopScore / 100, {
                    maximumFractionDigits: 0,
                    minimumFractionDigits: 0,
                  }),
                })}
              </p>
            ) : null}
          </div>
        ) : null}
      </div>
    </motion.li>
  );
};

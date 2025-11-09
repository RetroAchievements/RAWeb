import type { FC } from 'react';
import { Trans, useTranslation } from 'react-i18next';

import { calculateUnlockPercentage } from '@/common/utils/calculateUnlockPercentage';
import { cn } from '@/common/utils/cn';
import { formatPercentage } from '@/common/utils/l10n/formatPercentage';

interface ProgressBarMetaTextProps {
  achievement: App.Platform.Data.Achievement;
  playersTotal: number;
  variant: 'game' | 'event';

  shouldPrioritizeHardcoreStats?: boolean;
}

export const ProgressBarMetaText: FC<ProgressBarMetaTextProps> = ({
  achievement,
  playersTotal,
  variant,
  shouldPrioritizeHardcoreStats = false,
}) => {
  const { t } = useTranslation();

  const unlocksHardcoreTotal = achievement.unlocksHardcoreTotal ?? 0;
  const unlocksTotal = achievement.unlocksTotal ?? 0;
  const unlockPercentage = calculateUnlockPercentage(
    shouldPrioritizeHardcoreStats,
    unlocksHardcoreTotal,
    playersTotal,
    achievement.unlockPercentage,
  );

  return (
    <Trans
      i18nKey="<1>{{totalUnlocks, number}}</1> <2>({{totalHardcoreUnlocks, number}})</2> of <3>{{totalPlayers, number}}</3> <4>- {{unlockPercentage}}</4> <5>unlock rate</5>"
      values={{
        totalUnlocks: shouldPrioritizeHardcoreStats ? unlocksHardcoreTotal : unlocksTotal,
        totalHardcoreUnlocks: unlocksHardcoreTotal,
        totalPlayers: playersTotal,
        unlockPercentage: formatPercentage(unlockPercentage, {
          minimumFractionDigits: 2,
          maximumFractionDigits: 2,
        }),
      }}
      components={{
        1: (
          <span
            title={shouldPrioritizeHardcoreStats ? t('Hardcore unlocks') : t('Total unlocks')}
            className={cn(
              unlocksTotal === unlocksHardcoreTotal && unlocksHardcoreTotal > 0
                ? 'font-bold'
                : null,

              'cursor-help',
            )}
          />
        ),

        2: (
          <span
            className={cn(
              shouldPrioritizeHardcoreStats ||
                (unlocksTotal === unlocksHardcoreTotal && variant !== 'game')
                ? 'sr-only'
                : null,
              'cursor-help font-bold',
            )}
            title={t('Hardcore unlocks')}
          />
        ),

        3: <span title={t('Total players')} className="cursor-help" />,
        4: <span className="md:hidden" />,
        5: <span className="hidden sm:inline md:hidden" />,
      }}
    />
  );
};

import type { FC } from 'react';
import { Trans, useTranslation } from 'react-i18next';

import { useFormatPercentage } from '@/common/hooks/useFormatPercentage';
import { cn } from '@/common/utils/cn';

interface ProgressBarMetaTextProps {
  achievement: App.Platform.Data.Achievement;
  playersTotal: number;
  variant: 'game' | 'event';
}

export const ProgressBarMetaText: FC<ProgressBarMetaTextProps> = ({
  achievement,
  playersTotal,
  variant,
}) => {
  const { t } = useTranslation();
  const { formatPercentage } = useFormatPercentage();

  const unlocksHardcoreTotal = achievement.unlocksHardcore ?? 0;
  const unlocksTotal = achievement.unlocksTotal ?? 0;
  const unlockPercentage = achievement.unlockPercentage ? Number(achievement.unlockPercentage) : 0;

  return (
    <Trans
      i18nKey="<1>{{totalUnlocks, number}}</1> <2>({{totalHardcoreUnlocks, number}})</2> of <3>{{totalPlayers, number}}</3> <4>- {{unlockPercentage}}</4> <5>unlock rate</5>"
      values={{
        totalUnlocks: unlocksTotal,
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
            title={t('Total unlocks')}
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
              unlocksTotal === unlocksHardcoreTotal && variant !== 'game' ? 'sr-only' : null,
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

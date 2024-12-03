import type { FC } from 'react';

import { useGetAwardLabelFromPlayerBadge } from '@/common/hooks/useGetAwardLabelFromPlayerBadge';
import { cn } from '@/common/utils/cn';
import { AwardType } from '@/common/utils/generatedAppConstants';

interface PlayerBadgeIndicatorProps {
  playerBadge: App.Platform.Data.PlayerBadge;

  className?: string;
}

export const PlayerBadgeIndicator: FC<PlayerBadgeIndicatorProps> = ({ playerBadge, className }) => {
  const { getAwardLabelFromPlayerBadge } = useGetAwardLabelFromPlayerBadge();

  const label = getAwardLabelFromPlayerBadge(playerBadge);

  const { awardType, awardDataExtra } = playerBadge;

  return (
    <div
      role="img"
      aria-label={`${label} indicator`}
      className={cn(
        'h-2 w-2 rounded-full',

        awardType === AwardType.Mastery && awardDataExtra ? 'bg-[gold] light:bg-yellow-600' : '', // Mastered
        awardType === AwardType.Mastery && !awardDataExtra ? 'border border-yellow-600' : '', // Completed

        awardType === AwardType.GameBeaten && awardDataExtra ? 'bg-zinc-300' : '', // Beaten
        awardType === AwardType.GameBeaten && !awardDataExtra ? 'border border-zinc-400' : '', // Beaten (softcore)

        className,
      )}
    />
  );
};

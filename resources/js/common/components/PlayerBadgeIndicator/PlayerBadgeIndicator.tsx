import type { FC } from 'react';

import { AwardType } from '@/common/utils/generatedAppConstants';
import { getLabelFromPlayerBadge } from '@/common/utils/getLabelFromPlayerBadge';
import { cn } from '@/utils/cn';

type PlayerBadgeIndicatorProps = App.Platform.Data.PlayerBadge & { className?: string };

export const PlayerBadgeIndicator: FC<PlayerBadgeIndicatorProps> = ({
  awardType,
  awardDataExtra,
  className,
}) => {
  return (
    <div
      aria-label={`${getLabelFromPlayerBadge(awardType, awardDataExtra)} indicator`}
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

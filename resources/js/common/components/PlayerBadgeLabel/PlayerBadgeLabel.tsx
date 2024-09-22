import type { FC } from 'react';

import { AwardType } from '@/common/utils/generatedAppConstants';
import { getLabelFromPlayerBadge } from '@/common/utils/getLabelFromPlayerBadge';
import { cn } from '@/utils/cn';

type PlayerBadgeLabelProps = App.Platform.Data.PlayerBadge & {
  className?: string;
  isColorized?: boolean;
};

export const PlayerBadgeLabel: FC<PlayerBadgeLabelProps> = ({
  awardType,
  awardDataExtra,
  isColorized = true,
  className,
}) => {
  const label = getLabelFromPlayerBadge(awardType, awardDataExtra);

  return (
    <span
      className={cn(
        isColorized ? getLabelColorClassNames(awardType, awardDataExtra) : undefined,
        className,
      )}
    >
      {label}
    </span>
  );
};

function getLabelColorClassNames(awardType: number, awardDataExtra: number): string {
  // Mastered
  if (awardType === AwardType.Mastery && awardDataExtra) {
    return 'text-[gold] light:text-yellow-600';
  }

  // Completed
  if (awardType === AwardType.Mastery && !awardDataExtra) {
    return 'text-yellow-600';
  }

  // Beaten
  if (awardType === AwardType.GameBeaten && awardDataExtra) {
    return 'text-zinc-300';
  }

  // Beaten (softcore)
  if (awardType === AwardType.GameBeaten && !awardDataExtra) {
    return 'text-zinc-400';
  }

  return '';
}

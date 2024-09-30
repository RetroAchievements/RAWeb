import type { FC } from 'react';

import { AwardType } from '@/common/utils/generatedAppConstants';
import { getLabelFromPlayerBadge } from '@/common/utils/getLabelFromPlayerBadge';
import { cn } from '@/utils/cn';

type PlayerBadgeLabelProps = App.Platform.Data.PlayerBadge & {
  className?: string;
  isColorized?: boolean;
  variant?: 'base' | 'muted-group';
};

export const PlayerBadgeLabel: FC<PlayerBadgeLabelProps> = ({
  awardType,
  awardDataExtra,
  className,
  isColorized = true,
  variant = 'base',
}) => {
  const label = getLabelFromPlayerBadge(awardType, awardDataExtra);

  return (
    <span
      className={cn(
        isColorized ? getLabelColorClassNames(awardType, awardDataExtra, variant) : undefined,
        className,
      )}
    >
      {label}
    </span>
  );
};

function getLabelColorClassNames(
  awardType: number,
  awardDataExtra: number,
  variant: PlayerBadgeLabelProps['variant'],
): string {
  const baseColors: Record<number, string> = {
    [AwardType.Mastery]: awardDataExtra ? 'text-[gold] light:text-yellow-600' : 'text-yellow-600',
    [AwardType.GameBeaten]: awardDataExtra ? 'text-zinc-300' : 'text-zinc-400',
  };

  const mutedGroupColors: Record<number, string> = {
    [AwardType.Mastery]: awardDataExtra
      ? 'transition text-muted group-hover:text-[gold] group-hover:light:text-yellow-600' // Mastery
      : 'transition text-muted group-hover:text-yellow-600', // Completion

    [AwardType.GameBeaten]: awardDataExtra
      ? 'transition text-muted group-hover:text-zinc-300' // Beaten
      : 'transition text-muted group-hover:text-zinc-400', // Beaten (softcore)
  };

  if (variant === 'base') {
    return baseColors[awardType] ?? '';
  } else if (variant === 'muted-group') {
    return mutedGroupColors[awardType] ?? '';
  }

  return '';
}

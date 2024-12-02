import type { FC } from 'react';

import { useGetAwardLabelFromPlayerBadge } from '@/common/hooks/useGetAwardLabelFromPlayerBadge';
import { buildAwardLabelColorClassNames } from '@/common/utils/buildAwardLabelColorClassNames';
import { cn } from '@/common/utils/cn';

type PlayerBadgeLabelProps = {
  playerBadge: App.Platform.Data.PlayerBadge;

  className?: string;
  isColorized?: boolean;
  variant?: 'base' | 'muted-group';
};

export const PlayerBadgeLabel: FC<PlayerBadgeLabelProps> = ({
  playerBadge,
  className,
  isColorized = true,
  variant = 'base',
}) => {
  const { getAwardLabelFromPlayerBadge } = useGetAwardLabelFromPlayerBadge();

  const label = getAwardLabelFromPlayerBadge(playerBadge);

  return (
    <span
      className={cn(
        isColorized
          ? buildAwardLabelColorClassNames(
              playerBadge.awardType,
              playerBadge.awardDataExtra,
              variant,
            )
          : undefined,
        className,
      )}
    >
      {label}
    </span>
  );
};

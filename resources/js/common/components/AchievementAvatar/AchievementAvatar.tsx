import type { FC } from 'react';

import { useCardTooltip } from '@/common/hooks/useCardTooltip';
import type { BaseAvatarProps } from '@/common/models';
import { cn } from '@/common/utils/cn';

// TODO come up with some way to determine if the locked or unlocked badge should be shown
// this can be driven off `unlockedAt` or `unlockedHardcoreAt` from the `Achievement` model,
// but there may be cases we want to always show locked or always show unlocked.
// maybe an enum prop like `displayLockedStatus: 'always-locked' | 'always-unlocked' | 'auto'`
type AchievementAvatarProps = BaseAvatarProps &
  App.Platform.Data.Achievement & {
    showHardcoreUnlockBorder?: boolean;
  };

export const AchievementAvatar: FC<AchievementAvatarProps> = ({
  id,
  badgeUnlockedUrl, // see TODO above
  title,
  hasTooltip = true,
  showHardcoreUnlockBorder = true,
  showImage = true,
  showLabel = true,
  size = 32,
}) => {
  const { cardTooltipProps } = useCardTooltip({ dynamicType: 'achievement', dynamicId: id });

  return (
    <a
      href={route('achievement.show', { achievement: id })}
      className={cn('flex items-center', showHardcoreUnlockBorder ? 'gap-2.5' : 'gap-2')}
      {...(hasTooltip ? cardTooltipProps : undefined)}
    >
      {showImage ? (
        <img
          loading="lazy"
          decoding="async"
          width={size}
          height={size}
          src={badgeUnlockedUrl}
          alt={title ?? 'Achievement'}
          className={cn(
            'rounded-sm',
            showHardcoreUnlockBorder
              ? 'rounded-[1px] outline outline-2 outline-offset-1 outline-[gold] light:outline-amber-500'
              : null,
          )}
        />
      ) : null}

      {title && showLabel ? <span>{title}</span> : null}
    </a>
  );
};

import type { FC } from 'react';

import { useCardTooltip } from '@/common/hooks/useCardTooltip';
import type { BaseAvatarProps } from '@/common/models';

// TODO come up with some way to determine if the locked or unlocked badge should be shown
// this can be driven off `unlockedAt` or `unlockedHardcoreAt` from the `Achievement` model,
// but there may be cases we want to always show locked or always show unlocked.
// maybe an enum prop like `displayLockedStatus: 'always-locked' | 'always-unlocked' | 'auto'`
type AchievementAvatarProps = BaseAvatarProps & App.Platform.Data.Achievement;

export const AchievementAvatar: FC<AchievementAvatarProps> = ({
  id,
  badgeUnlockedUrl, // see TODO above
  title,
  showImage = true,
  showLabel = true,
  size = 32,
  hasTooltip = true,
}) => {
  const { cardTooltipProps } = useCardTooltip({ dynamicType: 'achievement', dynamicId: id });

  return (
    <a
      href={route('achievement.show', { achievement: id })}
      className="flex items-center gap-2"
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
          className="rounded-sm"
        />
      ) : null}

      {title && showLabel ? <span>{title}</span> : null}
    </a>
  );
};

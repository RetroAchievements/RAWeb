import type { FC, ReactNode } from 'react';

import { useCardTooltip } from '@/common/hooks/useCardTooltip';
import type { BaseAvatarProps } from '@/common/models';
import { cn } from '@/common/utils/cn';

// TODO come up with some way to determine if the locked or unlocked badge should be shown
// this can be driven off `unlockedAt` or `unlockedHardcoreAt` from the `Achievement` model,
// but there may be cases we want to always show locked or always show unlocked.
// maybe an enum prop like `displayLockedStatus: 'always-locked' | 'always-unlocked' | 'auto'.
type AchievementAvatarProps = BaseAvatarProps &
  App.Platform.Data.Achievement & {
    showHardcoreUnlockBorder?: boolean;
    showPointsInTitle?: boolean;
    sublabelSlot?: ReactNode;
  };

export const AchievementAvatar: FC<AchievementAvatarProps> = ({
  badgeUnlockedUrl,
  id,
  imgClassName,
  points,
  sublabelSlot,
  title,
  hasTooltip = true,
  showHardcoreUnlockBorder = true,
  showPointsInTitle = false,
  showImage = true,
  showLabel = true,
  size = 32,
}) => {
  const { cardTooltipProps } = useCardTooltip({ dynamicType: 'achievement', dynamicId: id });

  let titleLabel = title;
  if (showPointsInTitle) {
    titleLabel = `${title} (${points ?? 0})`;
  }

  const achievementLink = (label: React.ReactNode) => (
    <a
      href={route('achievement.show', { achievement: id })}
      className="max-w-fit"
      {...(hasTooltip ? cardTooltipProps : undefined)}
    >
      {label}
    </a>
  );

  return (
    <div
      className={cn('flex max-w-fit items-center', showHardcoreUnlockBorder ? 'gap-2.5' : 'gap-2')}
    >
      {showImage
        ? achievementLink(
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
                imgClassName,
              )}
            />,
          )
        : null}

      {sublabelSlot ? (
        <div className="flex flex-col">
          {title && showLabel ? achievementLink(<span>{titleLabel}</span>) : null}
          {sublabelSlot}
        </div>
      ) : (
        <>{title && showLabel ? achievementLink(<span>{titleLabel}</span>) : null}</>
      )}
    </div>
  );
};

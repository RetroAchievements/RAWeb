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
    variant?: 'base' | 'inline';
  };

export const AchievementAvatar: FC<AchievementAvatarProps> = ({
  badgeUnlockedUrl, // see TODO above
  id,
  imgClassName,
  points,
  sublabelSlot,
  title,
  hasTooltip = true,
  showHardcoreUnlockBorder = true,
  showImage = true,
  showLabel = true,
  showPointsInTitle = false,
  size = 32,
  variant = 'base',
}) => {
  const { cardTooltipProps } = useCardTooltip({ dynamicType: 'achievement', dynamicId: id });

  let titleLabel = title;
  if (showPointsInTitle) {
    titleLabel = `${title} (${points ?? 0})`;
  }

  const achievementLink = (children: React.ReactNode) => (
    <a
      href={route('achievement.show', { achievement: id })}
      className="max-w-fit"
      {...(hasTooltip ? cardTooltipProps : undefined)}
    >
      {children}
    </a>
  );

  if (!showLabel && showImage && badgeUnlockedUrl) {
    return achievementLink(
      <AchievementBadge
        badgeUrl={badgeUnlockedUrl}
        title={title}
        size={size}
        showHardcoreUnlockBorder={showHardcoreUnlockBorder}
        variant={variant}
        imgClassName={imgClassName}
      />,
    );
  }

  return (
    <div
      data-testid="ach-avatar-root"
      className={cn(
        variant === 'base' ? 'flex max-w-fit items-center' : null,
        variant === 'inline' ? 'inline-block min-h-[26px]' : null,

        showHardcoreUnlockBorder ? 'gap-2.5' : 'gap-2',
      )}
    >
      {showImage && badgeUnlockedUrl ? (
        <AchievementBadge
          badgeUrl={badgeUnlockedUrl}
          title={title}
          size={size}
          showHardcoreUnlockBorder={showHardcoreUnlockBorder}
          variant={variant}
          imgClassName={imgClassName}
        />
      ) : null}

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

type AchievementBadgeProps = Partial<AchievementAvatarProps> & {
  badgeUrl: string;
};

const AchievementBadge: FC<AchievementBadgeProps> = ({
  badgeUrl,
  title,
  size,
  showHardcoreUnlockBorder,
  variant,
  imgClassName,
}) => {
  return (
    <img
      loading="lazy"
      decoding="async"
      width={size}
      height={size}
      src={badgeUrl}
      alt={title ?? 'Achievement'}
      className={cn(
        'rounded-sm',

        showHardcoreUnlockBorder
          ? 'rounded-[1px] outline outline-2 outline-offset-1 outline-[gold] light:outline-amber-500'
          : null,

        variant === 'inline' ? 'mr-1.5' : null,

        imgClassName,
      )}
    />
  );
};

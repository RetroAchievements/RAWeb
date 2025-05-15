import type { FC, ReactNode } from 'react';
import { route } from 'ziggy-js';

import { useCardTooltip } from '@/common/hooks/useCardTooltip';
import type { BaseAvatarProps } from '@/common/models';
import { cn } from '@/common/utils/cn';

type AchievementAvatarProps = BaseAvatarProps &
  App.Platform.Data.Achievement & {
    displayLockedStatus?: 'auto' | 'locked' | 'unlocked' | 'unlocked-hardcore';
    showPointsInTitle?: boolean;
    sublabelSlot?: ReactNode;
    variant?: 'base' | 'inline';
  };

export const AchievementAvatar: FC<AchievementAvatarProps> = ({
  badgeLockedUrl,
  badgeUnlockedUrl,
  id,
  imgClassName,
  points,
  sublabelSlot,
  title,
  unlockedAt,
  unlockedHardcoreAt,
  displayLockedStatus = 'unlocked',
  hasTooltip = true,
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

  const derivedDisplayLockedStatus =
    displayLockedStatus === 'auto'
      ? getAutoLockStatus(unlockedHardcoreAt, unlockedAt)
      : displayLockedStatus;

  const badgeUrl = derivedDisplayLockedStatus === 'locked' ? badgeLockedUrl : badgeUnlockedUrl;

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
        badgeUrl={badgeUrl}
        title={title}
        size={size}
        displayLockedStatus={derivedDisplayLockedStatus}
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

        derivedDisplayLockedStatus === 'unlocked-hardcore' ? 'gap-2.5' : 'gap-2',
      )}
    >
      {showImage && badgeUrl ? (
        <AchievementBadge
          badgeUrl={badgeUrl}
          title={title}
          size={size}
          displayLockedStatus={derivedDisplayLockedStatus}
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
  displayLockedStatus: 'locked' | 'unlocked' | 'unlocked-hardcore';
};

const AchievementBadge: FC<AchievementBadgeProps> = ({
  badgeUrl,
  displayLockedStatus,
  imgClassName,
  size,
  title,
  variant,
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

        displayLockedStatus === 'unlocked-hardcore'
          ? 'rounded-[1px] outline outline-2 outline-offset-1 outline-[gold] light:outline-amber-500'
          : null,

        variant === 'inline' ? 'mr-1.5' : null,

        imgClassName,
      )}
    />
  );
};

function getAutoLockStatus(
  unlockedHardcoreAt?: string,
  unlockedAt?: string,
): 'locked' | 'unlocked' | 'unlocked-hardcore' {
  if (unlockedHardcoreAt) {
    return 'unlocked-hardcore';
  }

  if (unlockedAt) {
    return 'unlocked';
  }

  return 'locked';
}

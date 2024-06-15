import type { FC } from 'react';

import { useCardTooltip } from '@/common/hooks/useCardTooltip';

interface UserAvatarProps {
  displayName: string | null;

  hasTooltip?: boolean;
  // This is strongly typed so we don't wind up with 100 different possible sizes.
  // If possible, use one of these sane defaults. Only add another one if necessary.
  size?: 8 | 16 | 24 | 32 | 64 | 128;
}

export const UserAvatar: FC<UserAvatarProps> = ({ displayName, size = 32, hasTooltip = true }) => {
  const { cardTooltipProps } = useCardTooltip({ dynamicType: 'user', dynamicId: displayName });

  return (
    <a
      href={displayName ? route('user.show', [displayName]) : undefined}
      className="flex items-center gap-2"
      {...(hasTooltip && displayName ? cardTooltipProps : undefined)}
    >
      <img
        loading="lazy"
        decoding="async"
        width={size}
        height={size}
        src={`http://media.retroachievements.org/UserPic/${displayName}.png`}
        alt={displayName ?? 'Deleted User'}
        className="rounded-sm"
      />

      {displayName ? <span>{displayName}</span> : null}
    </a>
  );
};

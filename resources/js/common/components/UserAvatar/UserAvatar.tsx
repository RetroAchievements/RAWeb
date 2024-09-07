import type { FC } from 'react';

import { useCardTooltip } from '@/common/hooks/useCardTooltip';
import type { AvatarSize } from '@/common/models';

interface UserAvatarProps {
  displayName: string | null;

  hasTooltip?: boolean;
  size?: AvatarSize;
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

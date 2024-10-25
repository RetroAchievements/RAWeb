import type { FC } from 'react';

import { useCardTooltip } from '@/common/hooks/useCardTooltip';
import type { BaseAvatarProps } from '@/common/models';
import { cn } from '@/utils/cn';

type UserAvatarProps = BaseAvatarProps & App.Data.User;

export const UserAvatar: FC<UserAvatarProps> = ({
  displayName,
  deletedAt,
  hasTooltip = true,
  showImage = true,
  showLabel = true,
  size = 32,
}) => {
  const { cardTooltipProps } = useCardTooltip({ dynamicType: 'user', dynamicId: displayName });

  const canLinkToUser = displayName && !deletedAt;
  const Wrapper = canLinkToUser ? 'a' : 'div';

  return (
    <Wrapper
      href={canLinkToUser ? route('user.show', [displayName]) : undefined}
      className="flex items-center gap-2"
      {...(hasTooltip && canLinkToUser ? cardTooltipProps : undefined)}
    >
      {showImage ? (
        <img
          loading="lazy"
          decoding="async"
          width={size}
          height={size}
          src={`http://media.retroachievements.org/UserPic/${displayName}.png`}
          alt={displayName ?? 'Deleted User'}
          className="rounded-sm"
        />
      ) : null}

      {displayName && showLabel ? (
        <span className={cn(deletedAt ? 'line-through' : null)}>{displayName}</span>
      ) : null}
    </Wrapper>
  );
};

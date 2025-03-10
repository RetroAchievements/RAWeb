import type { FC } from 'react';

import { useCardTooltip } from '@/common/hooks/useCardTooltip';
import type { BaseAvatarProps } from '@/common/models';
import { cn } from '@/common/utils/cn';

type UserAvatarProps = BaseAvatarProps &
  App.Data.User & {
    labelClassName?: string;
    wrapperClassName?: string;
  };

export const UserAvatar: FC<UserAvatarProps> = ({
  avatarUrl,
  deletedAt,
  displayName,
  imgClassName,
  labelClassName,
  wrapperClassName,
  hasTooltip = true,
  showImage = true,
  showLabel = true,
  size = 32,
}) => {
  const { cardTooltipProps } = useCardTooltip({ dynamicType: 'user', dynamicId: displayName });

  const canLinkToUser = displayName && !deletedAt;
  const Wrapper = canLinkToUser ? 'a' : 'span';

  return (
    <Wrapper
      href={canLinkToUser ? route('user.show', [displayName]) : undefined}
      className={cn('flex max-w-fit items-center gap-2', wrapperClassName)}
      {...(hasTooltip && canLinkToUser ? cardTooltipProps : undefined)}
    >
      {showImage ? (
        <img
          loading="lazy"
          decoding="async"
          width={size}
          height={size}
          src={avatarUrl ?? 'https://media.retroachievements.org/UserPic/Server.png'}
          alt={displayName ?? 'Deleted User'}
          className={cn('rounded-sm', imgClassName)}
        />
      ) : null}

      {displayName && showLabel ? (
        <span className={cn(deletedAt ? 'line-through' : null, labelClassName)}>{displayName}</span>
      ) : null}
    </Wrapper>
  );
};

import type { FC } from 'react';
import { route } from 'ziggy-js';

import { useCardTooltip } from '@/common/hooks/useCardTooltip';
import type { BaseAvatarProps } from '@/common/models';
import { cn } from '@/common/utils/cn';

type UserAvatarProps = BaseAvatarProps &
  App.Data.User & {
    canLinkToUser?: boolean;
    labelClassName?: string;
    wrapperClassName?: string;
  };

export const UserAvatar: FC<UserAvatarProps> = ({
  avatarUrl,
  deletedAt,
  displayName,
  imgClassName,
  isGone,
  labelClassName,
  wrapperClassName,
  canLinkToUser = true,
  hasTooltip = true,
  showImage = true,
  showLabel = true,
  size = 32,
}) => {
  const { cardTooltipProps } = useCardTooltip({ dynamicType: 'user', dynamicId: displayName });

  const shouldLinkToUser = canLinkToUser && displayName && !deletedAt && !isGone;
  const Wrapper = shouldLinkToUser ? 'a' : 'span';

  return (
    <Wrapper
      href={shouldLinkToUser ? route('user.show', [displayName]) : undefined}
      className={cn('flex max-w-fit items-center gap-2', wrapperClassName)}
      {...(hasTooltip && shouldLinkToUser ? cardTooltipProps : undefined)}
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
        <span className={cn(deletedAt || isGone ? 'line-through' : null, labelClassName)}>
          {displayName}
        </span>
      ) : null}
    </Wrapper>
  );
};

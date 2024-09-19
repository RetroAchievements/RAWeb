import type { FC } from 'react';

import { useCardTooltip } from '@/common/hooks/useCardTooltip';
import type { BaseAvatarProps } from '@/common/models';

type GameAvatarProps = BaseAvatarProps & App.Platform.Data.Game;

export const GameAvatar: FC<GameAvatarProps> = ({
  id,
  badgeUrl,
  title,
  showImage = true,
  showLabel = true,
  size = 32,
  hasTooltip = true,
}) => {
  const { cardTooltipProps } = useCardTooltip({ dynamicType: 'game', dynamicId: id });

  return (
    <a
      href={route('game.show', { game: id })}
      className="flex items-center gap-2"
      {...(hasTooltip ? cardTooltipProps : undefined)}
    >
      {showImage ? (
        <img
          loading="lazy"
          decoding="async"
          width={size}
          height={size}
          src={badgeUrl}
          alt={title ?? 'Game'}
          className="rounded-sm"
        />
      ) : null}

      {title && showLabel ? <span>{title}</span> : null}
    </a>
  );
};

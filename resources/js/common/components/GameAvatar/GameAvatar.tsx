import type { FC } from 'react';

import { useCardTooltip } from '@/common/hooks/useCardTooltip';
import { usePageProps } from '@/common/hooks/usePageProps';
import type { BaseAvatarProps } from '@/common/models';

import { GameTitle } from '../GameTitle';

type GameAvatarProps = BaseAvatarProps &
  App.Platform.Data.Game & { showHoverCardProgressForUsername?: string };

export const GameAvatar: FC<GameAvatarProps> = ({
  id,
  badgeUrl,
  showHoverCardProgressForUsername,
  title,
  showImage = true,
  showLabel = true,
  size = 32,
  hasTooltip = true,
}) => {
  const { auth } = usePageProps();

  const { cardTooltipProps } = useCardTooltip({
    dynamicType: 'game',
    dynamicId: id,
    dynamicContext: showHoverCardProgressForUsername ?? auth?.user.displayName,
  });

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

      {title && showLabel ? <GameTitle title={title} /> : null}
    </a>
  );
};

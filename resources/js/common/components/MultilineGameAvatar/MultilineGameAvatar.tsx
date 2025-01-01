import type { FC } from 'react';

import { useCardTooltip } from '@/common/hooks/useCardTooltip';
import { usePageProps } from '@/common/hooks/usePageProps';
import type { BaseAvatarProps } from '@/common/models';

import { GameTitle } from '../GameTitle';

/**
 * Should only be used on table layouts, which themselves should be used
 * very sparingly (they cause a lot of issues for mobile).
 */

type MultilineGameAvatarProps = App.Platform.Data.Game &
  Pick<BaseAvatarProps, 'hasTooltip'> & {
    showHoverCardProgressForUsername?: string;
  };

export const MultilineGameAvatar: FC<MultilineGameAvatarProps> = ({
  id,
  badgeUrl,
  system,
  title,
  showHoverCardProgressForUsername,
  hasTooltip = true,
}) => {
  const { auth } = usePageProps();

  const { cardTooltipProps } = useCardTooltip({
    dynamicType: 'game',
    dynamicId: id,
    dynamicContext: showHoverCardProgressForUsername ?? auth?.user.displayName,
  });

  return (
    <div className="relative flex max-w-fit items-center gap-x-2">
      {/* Keep the image and game title in a single tooltipped container. Do not tooltip the system name. */}
      <a href={route('game.show', { game: id })} {...(hasTooltip ? cardTooltipProps : undefined)}>
        <img
          src={badgeUrl}
          alt={title}
          width={36}
          height={36}
          className="h-9 w-9"
          loading="lazy"
          decoding="async"
        />

        <p className="absolute left-7 top-0 mb-0.5 max-w-fit pl-4 text-xs font-medium">
          <GameTitle title={title} />
        </p>
      </a>

      <div>
        {/* Provide invisible space to slide the system underneath. */}
        <p className="invisible mb-0.5 max-w-fit text-xs font-medium">
          <GameTitle title={title} />
        </p>

        {system ? (
          <div data-testid="game-system" className="flex items-center gap-x-1">
            <span className="mt-px block text-xs tracking-tighter">{system.name}</span>
          </div>
        ) : null}
      </div>
    </div>
  );
};

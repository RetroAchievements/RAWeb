import type { FC } from 'react';
import { route } from 'ziggy-js';

import { GameTitle } from '@/common/components/GameTitle';
import { useCardTooltip } from '@/common/hooks/useCardTooltip';
import { usePageProps } from '@/common/hooks/usePageProps';

import { InertiaLink } from '../../InertiaLink';

interface AchievementGameTitleProps {
  game: App.Platform.Data.Game;
}

export const AchievementGameTitle: FC<AchievementGameTitleProps> = ({ game }) => {
  const { auth } = usePageProps();

  const { cardTooltipProps } = useCardTooltip({
    dynamicType: 'game',
    dynamicId: game.id,
    dynamicContext: auth?.user.displayName,
  });

  return (
    <InertiaLink
      href={route('game.show', { game: game.id })}
      prefetch="desktop-hover-only"
      {...cardTooltipProps}
    >
      <GameTitle title={game.title} isWordWrappingEnabled={true} />{' '}
      {`(${game.system?.nameShort ?? ''})`}
    </InertiaLink>
  );
};

import type { FC } from 'react';

import { GameTitle } from '@/common/components/GameTitle';
import { useCardTooltip } from '@/common/hooks/useCardTooltip';
import { usePageProps } from '@/common/hooks/usePageProps';

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
    <a href={route('game.show', { game })} {...cardTooltipProps}>
      <GameTitle title={game.title} isWordWrappingEnabled={true} />{' '}
      {`(${game.system?.nameShort ?? ''})`}
    </a>
  );
};

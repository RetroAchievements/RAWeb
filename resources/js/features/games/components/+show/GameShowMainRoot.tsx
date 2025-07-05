import type { FC } from 'react';

import { GameBreadcrumbs } from '@/common/components/GameBreadcrumbs';
import { PlayableHeader } from '@/common/components/PlayableHeader';
import { PlayableMainMedia } from '@/common/components/PlayableMainMedia';
import { PlayableMobileMediaCarousel } from '@/common/components/PlayableMobileMediaCarousel';
import { usePageProps } from '@/common/hooks/usePageProps';

import { GameAchievementSetsContainer } from '../GameAchievementSetsContainer';
import { GameCommentList } from '../GameCommentList';
import { GameHeaderSlotContent } from '../GameHeaderSlotContent';
import { GameRecentPlayers } from '../GameRecentPlayers';

export const GameShowMainRoot: FC = () => {
  const { game } = usePageProps<App.Platform.Data.GameShowPageProps>();

  if (!game.badgeUrl || !game.system?.iconUrl) {
    return null;
  }

  return (
    <div data-testid="game-show" className="flex flex-col gap-3">
      <GameBreadcrumbs game={game} system={game.system} />
      <PlayableHeader
        badgeUrl={game.badgeUrl}
        systemIconUrl={game.system.iconUrl}
        systemLabel={game.system.name}
        title={game.title}
      >
        <GameHeaderSlotContent />
      </PlayableHeader>

      <div className="mt-2 hidden sm:block">
        <PlayableMainMedia
          imageIngameUrl={game.imageIngameUrl!}
          imageTitleUrl={game.imageTitleUrl!}
        />
      </div>

      <div className="-mx-3 sm:hidden">
        <PlayableMobileMediaCarousel
          imageIngameUrl={game.imageIngameUrl!}
          imageTitleUrl={game.imageTitleUrl!}
        />
      </div>

      <div className="flex flex-col gap-6">
        <GameAchievementSetsContainer game={game} />
        <GameRecentPlayers />
        <GameCommentList />
      </div>
    </div>
  );
};

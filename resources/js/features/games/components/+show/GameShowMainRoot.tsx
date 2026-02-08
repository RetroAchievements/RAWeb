import { useAtomValue } from 'jotai';
import type { FC } from 'react';

import { MatureContentWarningDialog } from '@/common/components/MatureContentWarningDialog';
import { PlayableMainMedia } from '@/common/components/PlayableMainMedia';
import { usePageProps } from '@/common/hooks/usePageProps';

import { currentListViewAtom } from '../../state/games.atoms';
import { getAllPageAchievements } from '../../utils/getAllPageAchievements';
import { AchievementSetEmptyState } from '../AchievementSetEmptyState';
import { GameAchievementSetsContainer } from '../GameAchievementSetsContainer';
import { GameCommentList } from '../GameCommentList';
import { GameRecentPlayers } from '../GameRecentPlayers';
import { ResetAllProgressDialog } from '../ResetAllProgressDialog';

export const GameShowMainRoot: FC = () => {
  const { game, hasMatureContent, isViewingPublishedAchievements, targetAchievementSetId } =
    usePageProps<App.Platform.Data.GameShowPageProps>();

  const currentListView = useAtomValue(currentListViewAtom);

  if (!game.badgeUrl || !game.system?.iconUrl) {
    return null;
  }

  const allPageAchievements = getAllPageAchievements(
    game.gameAchievementSets!,
    targetAchievementSetId,
  );

  return (
    <div data-testid="game-show" className="flex flex-col gap-3">
      {hasMatureContent ? <MatureContentWarningDialog /> : null}
      {allPageAchievements.length ? <ResetAllProgressDialog /> : null}

      <PlayableMainMedia
        imageIngameUrl={game.imageIngameUrl!}
        imageTitleUrl={game.imageTitleUrl!}
      />

      <div className="flex flex-col gap-6">
        <div className="flex flex-col">
          <GameAchievementSetsContainer game={game} />

          {!allPageAchievements.length && currentListView === 'achievements' ? (
            <AchievementSetEmptyState />
          ) : null}
        </div>

        {isViewingPublishedAchievements ? <GameRecentPlayers /> : null}
        {isViewingPublishedAchievements ? <GameCommentList /> : null}
      </div>
    </div>
  );
};

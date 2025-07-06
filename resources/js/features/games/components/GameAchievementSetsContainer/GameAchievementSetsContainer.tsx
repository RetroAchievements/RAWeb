import { type FC } from 'react';
import { useTranslation } from 'react-i18next';

import { EmptyState } from '@/common/components/EmptyState';
import { usePageProps } from '@/common/hooks/usePageProps';

import { GameAchievementSet } from './GameAchievementSet/GameAchievementSet';

interface GameAchievementSetsContainerProps {
  game: App.Platform.Data.Game;
}

export const GameAchievementSetsContainer: FC<GameAchievementSetsContainerProps> = ({ game }) => {
  const { targetAchievementSetId } = usePageProps<App.Platform.Data.GameShowPageProps>();

  const { t } = useTranslation();

  if (!game.gameAchievementSets?.length) {
    return (
      <div className="rounded bg-embed">
        <EmptyState shouldShowImage={false}>
          {t("There aren't any achievements for this game yet.")}
        </EmptyState>
      </div>
    );
  }

  const setsToShow = targetAchievementSetId
    ? game.gameAchievementSets.filter((gas) => gas.achievementSet.id === targetAchievementSetId)
    : game.gameAchievementSets;

  return (
    <div data-testid="game-achievement-sets" className="flex flex-col gap-4">
      {setsToShow.map((gameAchievementSet) => (
        <GameAchievementSet
          key={`gas-${gameAchievementSet.id}`}
          achievements={gameAchievementSet.achievementSet.achievements}
          gameAchievementSet={gameAchievementSet}
          isOnlySetForGame={setsToShow.length === 1}
        />
      ))}
    </div>
  );
};

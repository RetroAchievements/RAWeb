import { type FC } from 'react';
import { useTranslation } from 'react-i18next';

import { EmptyState } from '@/common/components/EmptyState';
import { usePageProps } from '@/common/hooks/usePageProps';

import { GameAchievementSet } from './GameAchievementSet/GameAchievementSet';
import { SetSelectionTabs } from './SetSelectionTabs';

interface GameAchievementSetsContainerProps {
  game: App.Platform.Data.Game;
}

export const GameAchievementSetsContainer: FC<GameAchievementSetsContainerProps> = ({ game }) => {
  const { selectableGameAchievementSets, targetAchievementSetId } =
    usePageProps<App.Platform.Data.GameShowPageProps>();

  const { t } = useTranslation();

  // TODO can this case still actually happen? we may be able to remove this
  if (!game.gameAchievementSets?.length) {
    return (
      <div className="rounded bg-embed">
        <EmptyState shouldShowImage={false}>
          {t("There aren't any achievements for this game yet.")}
        </EmptyState>
      </div>
    );
  }

  const setsToShowContent = targetAchievementSetId
    ? game.gameAchievementSets.filter((gas) => gas.achievementSet.id === targetAchievementSetId)
    : game.gameAchievementSets;

  return (
    <div data-testid="game-achievement-sets" className="flex flex-col gap-4">
      {selectableGameAchievementSets.length > 1 ? (
        <div className="-mb-3 flex w-full items-center gap-4 rounded bg-embed px-2 py-1.5">
          <SetSelectionTabs activeTab={targetAchievementSetId} />
        </div>
      ) : null}

      {setsToShowContent.map((gameAchievementSet) => (
        <GameAchievementSet
          key={`gas-${gameAchievementSet.id}`}
          achievements={gameAchievementSet.achievementSet.achievements}
          gameAchievementSet={gameAchievementSet}
        />
      ))}
    </div>
  );
};

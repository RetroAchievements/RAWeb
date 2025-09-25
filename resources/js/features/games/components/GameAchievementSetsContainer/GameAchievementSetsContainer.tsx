import { type FC } from 'react';

import { usePageProps } from '@/common/hooks/usePageProps';
import { cn } from '@/common/utils/cn';

import { GameAchievementSet } from './GameAchievementSet/GameAchievementSet';
import { SetSelectionTabs } from './SetSelectionTabs';

interface GameAchievementSetsContainerProps {
  game: App.Platform.Data.Game;
}

export const GameAchievementSetsContainer: FC<GameAchievementSetsContainerProps> = ({ game }) => {
  const { selectableGameAchievementSets, targetAchievementSetId } =
    usePageProps<App.Platform.Data.GameShowPageProps>();

  const setsToShowContent = targetAchievementSetId
    ? game.gameAchievementSets!.filter((gas) => gas.achievementSet.id === targetAchievementSetId)
    : game.gameAchievementSets!;

  return (
    <div data-testid="game-achievement-sets" className="flex flex-col gap-4">
      {selectableGameAchievementSets.length > 1 ? (
        <div
          className={cn(
            '-mb-3 flex w-full items-center gap-4 rounded bg-embed px-2 py-1.5',
            'light:bg-white',
          )}
        >
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

import { type FC, useState } from 'react';
import { useTranslation } from 'react-i18next';

import { AchievementSortButton } from '@/common/components/AchievementSortButton';
import { EmptyState } from '@/common/components/EmptyState';
import type { AchievementSortOrder } from '@/common/models';

import { GameAchievementSet } from './GameAchievementSet/GameAchievementSet';

interface GameAchievementSetsContainerProps {
  game: App.Platform.Data.Game;
}

export const GameAchievementSetsContainer: FC<GameAchievementSetsContainerProps> = ({ game }) => {
  const { t } = useTranslation();

  const [currentSort, setCurrentSort] = useState<AchievementSortOrder>('normal');

  if (!game.gameAchievementSets?.length) {
    return (
      <div className="rounded bg-embed">
        <EmptyState shouldShowImage={false}>
          {t("There aren't any achievements for this game yet.")}
        </EmptyState>
      </div>
    );
  }

  return (
    <div data-testid="game-achievement-sets" className="flex flex-col gap-4">
      <div className="flex w-full justify-between">
        <AchievementSortButton
          value={currentSort}
          onChange={(newValue) => setCurrentSort(newValue)}
          availableSortOrders={[
            'normal',
            '-normal',
            'wonBy',
            '-wonBy',
            'points',
            '-points',
            'title',
            '-title',
            'type',
            '-type',
          ]}
        />
      </div>

      {game.gameAchievementSets.map((gameAchievementSet) => (
        <GameAchievementSet
          key={`gas-${gameAchievementSet.id}`}
          achievements={gameAchievementSet.achievementSet.achievements}
          currentSort={currentSort}
          gameAchievementSet={gameAchievementSet}
          isOnlySetForGame={game.gameAchievementSets?.length === 1}
        />
      ))}
    </div>
  );
};

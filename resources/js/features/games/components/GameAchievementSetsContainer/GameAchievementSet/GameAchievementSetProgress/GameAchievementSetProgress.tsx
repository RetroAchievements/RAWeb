import type { FC } from 'react';

import { usePageProps } from '@/common/hooks/usePageProps';

import { BeatenProgressIndicator } from './BeatenProgressIndicator';
import { MasteredProgressIndicator } from './MasteredProgressIndicator';

interface GameAchievementSetProgressProps {
  achievements: App.Platform.Data.Achievement[];
  gameAchievementSet: App.Platform.Data.GameAchievementSet;
}

export const GameAchievementSetProgress: FC<GameAchievementSetProgressProps> = ({
  achievements,
  gameAchievementSet,
}) => {
  const { auth, backingGame, game } = usePageProps<App.Platform.Data.GameShowPageProps>();

  if (!auth?.user) {
    return null;
  }

  return (
    <div className="flex items-center gap-4">
      <div className="flex h-full items-center">
        <MasteredProgressIndicator
          achievements={achievements}
          gameAchievementSet={gameAchievementSet}
        />

        {backingGame.id === game.id ? (
          <BeatenProgressIndicator achievements={achievements} />
        ) : null}
      </div>
    </div>
  );
};

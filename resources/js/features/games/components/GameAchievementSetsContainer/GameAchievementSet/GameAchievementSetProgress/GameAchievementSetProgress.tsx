import type { FC } from 'react';

import { usePageProps } from '@/common/hooks/usePageProps';

import { BeatenProgressIndicator } from './BeatenProgressIndicator';
import { MasteredProgressIndicator } from './MasteredProgressIndicator';
import { PlaytimeIndicator } from './PlaytimeIndicator';

interface GameAchievementSetProgressProps {
  achievements: App.Platform.Data.Achievement[];
  gameAchievementSet: App.Platform.Data.GameAchievementSet;
}

export const GameAchievementSetProgress: FC<GameAchievementSetProgressProps> = ({
  achievements,
  gameAchievementSet,
}) => {
  const { auth, backingGame, game, isViewingPublishedAchievements } =
    usePageProps<App.Platform.Data.GameShowPageProps>();

  if (!auth?.user) {
    return null;
  }

  const canShowAwardIndicators = isViewingPublishedAchievements && achievements.length;

  return (
    <div className="flex items-center gap-4">
      <div className="flex h-full items-center">
        {canShowAwardIndicators ? (
          <MasteredProgressIndicator
            achievements={achievements}
            gameAchievementSet={gameAchievementSet}
          />
        ) : null}

        {backingGame.id === game.id ? (
          <>
            {canShowAwardIndicators ? (
              <BeatenProgressIndicator achievements={achievements} />
            ) : null}

            <PlaytimeIndicator showDivider={!!canShowAwardIndicators} />
          </>
        ) : null}
      </div>
    </div>
  );
};

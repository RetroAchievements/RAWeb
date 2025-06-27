import { type FC } from 'react';

import { usePageProps } from '@/common/hooks/usePageProps';

import { AchievementAuthorsDisplay } from './AchievementAuthorsDisplay';
import { ArtworkCreditsDisplay } from './ArtworkCreditsDisplay';
import { CodeCreditsDisplay } from './CodeCreditsDisplay';
import { DesignCreditsDisplay } from './DesignCreditsDisplay';

export const AchievementSetCredits: FC = () => {
  const { aggregateCredits } = usePageProps<App.Platform.Data.GameShowPageProps>();

  if (!aggregateCredits) {
    return null;
  }

  // All deduplication is now handled server-side.
  const hasArtworkCredits = aggregateCredits.achievementsArtwork.length > 0;
  const hasCodeCredits =
    aggregateCredits.achievementsLogic.length > 0 ||
    aggregateCredits.achievementsMaintainers.length > 0;
  const hasDesignCredits = aggregateCredits.achievementsDesign.length > 0;

  return (
    <div
      data-testid="set-credits"
      className="hidden items-center justify-between text-neutral-300 light:text-neutral-700 sm:flex"
    >
      <div className="flex w-full items-center rounded py-1 lg:flex-col lg:items-start lg:gap-2 xl:flex-row xl:items-center xl:gap-0">
        <div className="flex flex-wrap items-center gap-x-2 gap-y-1 xl:gap-4">
          <AchievementAuthorsDisplay authors={aggregateCredits.achievementsAuthors} />

          {hasArtworkCredits ? (
            <ArtworkCreditsDisplay
              achievementArtworkCredits={aggregateCredits.achievementsArtwork}
              badgeArtworkCredits={aggregateCredits.achievementSetArtwork}
            />
          ) : null}

          {hasCodeCredits ? (
            <CodeCreditsDisplay
              logicCredits={aggregateCredits.achievementsLogic}
              maintainerCredits={aggregateCredits.achievementsMaintainers}
            />
          ) : null}

          {hasDesignCredits ? (
            <DesignCreditsDisplay
              designCredits={aggregateCredits.achievementsDesign}
              testingCredits={aggregateCredits.achievementsTesting}
              writingCredits={aggregateCredits.achievementsWriting}
            />
          ) : null}
        </div>
      </div>
    </div>
  );
};
